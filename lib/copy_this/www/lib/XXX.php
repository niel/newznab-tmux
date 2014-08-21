<?php
require_once(WWW_DIR . "/lib/framework/db.php");
require_once(WWW_DIR . "/lib/releaseimage.php");
require_once(WWW_DIR . "/lib/IAFD.php");
require_once(WWW_DIR . "/lib/ADE.php");
require_once(WWW_DIR . "/lib/AEBN.php");
require_once(WWW_DIR . "/lib/Hotmovies.php");
require_once(WWW_DIR . "/lib/Popporn.php");
require_once(WWW_DIR . "../misc/update_scripts/nix_scripts/tmux/lib/ColorCLI.php");



/**
 * Class XXX
 */
class XXX
{
	/**
	 * @var DB
	 */
	public $db;

	/**
	 * We used AdultDVDEmpire or PopPorn class -- used for template and trailer information
	 *
	 * @var string
	 */
	protected $whichclass = '';

	/**
	 * Current title being passed through various sites/api's.
	 *
	 * @var string
	 */
	protected $currentTitle = '';

	/**
	 * @var ReleaseImage
	 */
	protected $releaseImage;

	protected $currentRelID;

	protected $movieqty;

	protected $showPasswords;

	protected $cookie;

	/**
	 * @param $echo
	 * @internal param $releaseImage
	 * @internal param $db
	 */
	public function __construct($echo=false)
	{
		$this->db = new DB();
		$this->releaseImage = new ReleaseImage();
		$t = new Tmux();
		$this->tmux = $t->get();
		$s = new Sites();
		$this->site = $s->get();
		$this->c = new ColorCLI();

		$this->movieqty = ($this->site->maxxxxprocessed != '') ? $this->site->maxxxxprocessed : 100;
		$this->showPasswords = ($this->site->showpasswordedrelease != '') ? $this->site->showpasswordedrelease : 0;
		$this->echooutput = $echo;
		$this->imgSavePath = WWW_DIR . 'covers/xxx/';
		$this->cookie = WWW_DIR . 'tmp/xxx.cookie';
	}

	/**
	 * Get info for a xxx id.
	 *
	 * @param int $xxxid
	 *
	 * @return array|bool
	 */
	public function getXXXInfo($xxxid)
	{
		return $this->db->queryOneRow(sprintf("SELECT *, UNCOMPRESS(plot) AS plot FROM xxxinfo WHERE id = %d", $xxxid));
	}

	/**
	 * Get movies for movie-list admin page.
	 *
	 * @param int $start
	 * @param int $num
	 *
	 * @return array
	 */
	public function getRange($start, $num)
	{
		return $this->db->query(
			sprintf('
				SELECT *,
				UNCOMPRESS(plot) AS plot
				FROM xxxinfo
				ORDER BY createddate DESC %s',
				($start === false ? '' : ' LIMIT ' . $num . ' OFFSET ' . $start)
			)
		);
	}

	/**
	 * Get count of movies for movie-list admin page.
	 *
	 * @return int
	 */
	public function getCount()
	{
		$res = $this->db->queryOneRow('SELECT COUNT(id) AS num FROM xxxinfo');

		return ($res === false ? 0 : $res['num']);
	}

	/**
	 * Get count of movies for movies browse page.
	 *
	 * @param       $cat
	 * @param       $maxAge
	 * @param array $excludedCats
	 *
	 * @return int
	 */
	public function getXXXCount($cat, $maxAge = -1, $excludedCats = array())
	{
		$catSearch = $this->formCategorySearchSQL($cat);

		$res = $this->db->queryOneRow(
			sprintf("
				SELECT COUNT(DISTINCT r.xxxinfo_id) AS num
				FROM releases r
				INNER JOIN xxxinfo m ON m.id = r.xxxinfo_id
				WHERE r.nzbstatus = 1
				AND m.cover = 1
				AND m.title != ''
				AND r.passwordstatus <= %d
				AND %s %s %s %s ",
				$this->showPasswords,
				$this->getBrowseBy(),
				$catSearch,
				($maxAge > 0
					? 'AND r.postdate > NOW() - INTERVAL ' . $maxAge . 'DAY '
					: ''
				),
				(count($excludedCats) > 0 ? ' AND r.categoryID NOT IN (' . implode(',', $excludedCats) . ')' : '')
			)
		);

		return ($res === false ? 0 : $res['num']);
	}

	/**
	 * Get movie releases with covers for xxx browse page.
	 *
	 * @param       $cat
	 * @param       $start
	 * @param       $num
	 * @param       $orderBy
	 * @param       $maxAge
	 * @param array $excludedCats
	 *
	 * @return array
	 */
	public function getXXXRange($cat, $start, $num, $orderBy, $maxAge = -1, $excludedCats = array())
	{
		$order = $this->getXXXOrder($orderBy);
		$sql = sprintf("
			SELECT
			GROUP_CONCAT(r.ID ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_id,
			GROUP_CONCAT(r.rarinnerfilecount ORDER BY r.postdate DESC SEPARATOR ',') as grp_rarinnerfilecount,
			GROUP_CONCAT(r.haspreview ORDER BY r.postdate DESC SEPARATOR ',') AS grp_haspreview,
			GROUP_CONCAT(r.passwordstatus ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_password,
			GROUP_CONCAT(r.guid ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_guid,
			GROUP_CONCAT(rn.ID ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_nfoid,
			GROUP_CONCAT(groups.name ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_grpname,
			GROUP_CONCAT(r.searchname ORDER BY r.postdate DESC SEPARATOR '#') AS grp_release_name,
			GROUP_CONCAT(r.postdate ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_postdate,
			GROUP_CONCAT(r.size ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_size,
			GROUP_CONCAT(r.totalpart ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_totalparts,
			GROUP_CONCAT(r.comments ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_comments,
			GROUP_CONCAT(r.grabs ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_grabs,
			m.*, UNCOMPRESS(m.plot) AS plot, groups.name AS group_name, rn.id as nfoid FROM releases r
			LEFT OUTER JOIN groups ON groups.ID = r.groupID
			LEFT OUTER JOIN releasenfo rn ON rn.releaseID = r.ID
			INNER JOIN xxxinfo m ON m.id = r.xxxinfo_id
			WHERE r.nzbstatus = 1
			AND m.cover = 1
			AND m.title != ''
			AND r.passwordstatus <= %d AND %s %s %s %s
			GROUP BY m.id ORDER BY %s %s %s",
			$this->showPasswords,
			$this->getBrowseBy(),
			$this->formCategorySearchSQL($cat),
			($maxAge > 0
				? 'AND r.postdate > NOW() - INTERVAL ' . $maxAge . 'DAY '
				: ''
			),
			(count($excludedCats) > 0 ? ' AND r.categoryID NOT IN (' . implode(',', $excludedCats) . ')' : ''),
			$order[0],
			$order[1],
			($start === false ? '' : ' LIMIT ' . $num . ' OFFSET ' . $start)
		);

		return $this->db->query($sql);
	}

	/**
	 * Form category search SQL.
	 *
	 * @param $cat
	 *
	 * @return string
	 */
	protected function formCategorySearchSQL($cat)
	{
		$catSearch = '';
		if (count($cat) > 0 && $cat[0] != -1) {
			$catSearch = '(';
			$Category = new Category();
			foreach ($cat as $category) {
				if ($category != -1) {

					if ($Category->isParent($category)) {
						$children = $Category->getChildren($category);
						$chList = '-99';
						foreach ($children as $child) {
							$chList .= ', ' . $child['ID'];
						}

						if ($chList != '-99') {
							$catSearch .= ' r.categoryID IN (' . $chList . ') OR ';
						}
					} else {
						$catSearch .= sprintf(' r.categoryID = %d OR ', $category);
					}
				}
			}
			$catSearch .= '1=2)';
		}

		return $catSearch;
	}

	/**
	 * Get the order type the user requested on the movies page.
	 *
	 * @param $orderBy
	 *
	 * @return array
	 */
	protected function getXXXOrder($orderBy)
	{
		$orderArr = explode('_', (($orderBy == '') ? 'MAX(r.postdate)' : $orderBy));
		switch ($orderArr[0]) {
			case 'title':
				$orderField = 'm.title';
				break;
			case 'posted':
			default:
				$orderField = 'MAX(r.postdate)';
				break;
		}

		return array($orderField, ((isset($orderArr[1]) && preg_match('/^asc|desc$/i', $orderArr[1])) ? $orderArr[1] : 'desc'));
	}

	/**
	 * Order types for xxx page.
	 *
	 * @return array
	 */
	public function getXXXOrdering()
	{
		return array('title_asc', 'title_desc');
	}

	/**
	 * @return string
	 */
	protected function getBrowseBy()
	{
		$browseBy = ' ';
		$browseByArr = array('title', 'director', 'actors', 'genre', 'ID');
		foreach ($browseByArr as $bb) {
			if (isset($_REQUEST[$bb]) && !empty($_REQUEST[$bb])) {
				$bbv = stripslashes($_REQUEST[$bb]);
				if ($bb == "genre") {
					$bbv = $this->getgenreid($bbv);
				}
				if ($bb == 'ID') {
					$browseBy .= 'm.' . $bb . '=' . $bbv . ' AND ';
				} else {
					$browseBy .= 'm.' . $bb . ' LIKE (' . $this->db->escapeString('%' . $bbv . '%') . ') AND ';
				}
			}
		}

		return $browseBy;
	}

	/**
	 * Create click-able links to actors/genres/directors/etc..
	 *
	 * @param $data
	 * @param $field
	 *
	 * @return string
	 */
	public function makeFieldLinks($data, $field)
	{
		if (!isset($data[$field]) || $data[$field] == '') {
			return '';
		}

		$tmpArr = explode(',', $data[$field]);
		$newArr = array();
		$i = 0;
		foreach ($tmpArr as $ta) {
			if ($field == "genre") {
				$ta = $this->getGenres(true, $ta);
				$ta = $ta["title"];
			}
			if ($i > 5) {
				break;
			} //only use first 6
			$newArr[] = '<a href="' . WWW_TOP . '/xxx?' . $field . '=' . urlencode($ta) . '" title="' . $ta . '">' . $ta . '</a>';
			$i++;
		}

		return implode(', ', $newArr);
	}

	/**
	 * Update movie on movie-edit page.
	 *
	 * @param $id
	 * @param $title
	 * @param $tagline
	 * @param $plot
	 * @param $genre
	 * @param $director
	 * @param $actors
	 * @param $cover
	 * @param $backdrop
	 */
	public function update(
		$id = '', $title = '', $tagline = '', $plot = '', $genre = '', $director = '',
		$actors = '', $cover = '', $backdrop = ''
	)
	{
		if (!empty($id)) {

			$this->db->queryExec(
				sprintf("
					UPDATE xxxinfo
					SET %s, %s, %s, %s, %s, %s, %d, %d, updateddate = NOW()
					WHERE ID = %d",
					(empty($title) ? '' : 'title = ' . $this->db->escapeString($title)),
					(empty($tagLine) ? '' : 'tagline = ' . $this->db->escapeString($tagLine)),
					(empty($plot) ? '' : 'plot = ' . $this->db->escapeString($plot)),
					(empty($genre) ? '' : 'genre = ' . $this->db->escapeString($genre)),
					(empty($director) ? '' : 'director = ' . $this->db->escapeString($director)),
					(empty($actors) ? '' : 'actors = ' . $this->db->escapeString($actors)),
					(empty($cover) ? '' : 'cover = ' . $cover),
					(empty($backdrop) ? '' : 'backdrop = ' . $backdrop),
					$id
				)
			);
		}
	}

	/**
	 * Fetch xxx info for the movie.
	 *
	 * @param $xxxmovie
	 *
	 * @return bool
	 */
	public function updateXXXInfo($xxxmovie)
	{

		$res = false;
		$this->whichclass = '';

		$iafd = new IAFD();
		$iafd->searchterm = $xxxmovie;
		if ($iafd->findme() !== false) {
			switch ($iafd->classused) {
				case "ade":
					$mov = new ADE();
					$mov->directlink = $iafd->directurl;
					$res = $mov->getdirect();
					$res['title'] = $iafd->title;
					$res['directurl'] = (string)$iafd->directurl;
					$this->whichclass = $iafd->classused;
					$this->c->doEcho($this->c->primary("Fetching XXX info from IAFD: Adult DVD Empire"));
					break;
				case "hm":
					$mov = new Hotmovies();
					$mov->directlink = $iafd->directurl;
					$res = $mov->getdirect();
					$res['title'] = $iafd->title;
					$res['directurl'] = (string)$iafd->directurl;
					$this->whichclass = $iafd->classused;
					$this->c->doEcho($this->c->primary("Fetching XXX info from IAFD: Hot Movies"));
					break;
				default:
					$res = false;

			}
		} else {
			$res = false;
		}

		if ($res === false) {
			$this->whichclass = "aebn";
			$mov = new AEBN();
			$mov->cookie = $this->cookie;
			$mov->searchterm = $xxxmovie;
			$res = $mov->search();
			if ($res === false) {
				$this->whichclass = "ade";
				$mov = new ADE();
				$mov->searchterm = $xxxmovie;
				$res = $mov->search();
			}

			if ($res === false) {
				$this->whichclass = "hm";
				$mov = new Hotmovies();
				$mov->cookie = $this->cookie;
				$mov->searchterm = $xxxmovie;
				$res = $mov->search();
			}

			if ($res === false) {
				$this->whichclass = "pop";
				$mov = new Popporn();
				$mov->cookie = $this->cookie;
				$mov->searchterm = $xxxmovie;
				$res = $mov->search();
			}

			// If a result is true getall information.
			if ($res !== false) {
				if ($this->echooutput) {
					$fromstr = null;
					switch ($this->whichclass) {
						case "aebn":
							$fromstr = "AEBN";
							break;
						case "ade":
							$fromstr = "Adult DVD Empire";
							break;
						case "pop":
							$fromstr = "PopPorn";
							break;
						case "hm":
							$fromstr = "Hot Movies";
							break;
						default:
							$fromstr = null;

					}
					$this->c->doEcho($this->c->primary("Fetching XXX info from: " . $fromstr));
				}
				$res = $mov->_getall();
			} else {
				// Nothing was found, go ahead and set to -2 :(
				return false;
			}
		}
		$mov = array();

		$mov['trailers'] = (isset($res['trailers'])) ? serialize($res['trailers']) : '';
		$mov['extras'] = (isset($res['extras'])) ? serialize($res['extras']) : '';
		$mov['productinfo'] = (isset($res['productinfo'])) ? serialize($res['productinfo']) : '';
		$mov['backdrop'] = (isset($res['backcover'])) ? $res['backcover'] : '';
		$mov['cover'] = (isset($res['boxcover'])) ? $res['boxcover'] : '';
		$res['cast'] = (isset($res['cast'])) ? join(",", $res['cast']) : '';
		$res['genres'] = (isset($res['genres'])) ? $this->getgenreid($res['genres']) : '';
		$mov['title'] = html_entity_decode($res['title'], ENT_QUOTES, 'UTF-8');
		$mov['plot'] = (isset($res['sypnosis'])) ? html_entity_decode($res['sypnosis'], ENT_QUOTES, 'UTF-8') : '';
		$mov['tagline'] = (isset($res['tagline'])) ? html_entity_decode($res['tagline'], ENT_QUOTES, 'UTF-8') : '';
		$mov['genre'] = html_entity_decode($res['genres'], ENT_QUOTES, 'UTF-8');
		$mov['director'] = (isset($res['director'])) ? html_entity_decode($res['director'], ENT_QUOTES, 'UTF-8') : '';
		$mov['actors'] = html_entity_decode($res['cast'], ENT_QUOTES, 'UTF-8');
		$mov['directurl'] = html_entity_decode($res['directurl'], ENT_QUOTES, 'UTF-8');
		$mov['classused'] = $this->whichclass;
		$check = $this->db->queryOneRow(sprintf('SELECT id FROM xxxinfo WHERE title = %s', $this->db->escapeString($mov['title'])));
		$xxxID = null;
		if ($check === false) {
			$xxxID = $this->db->queryInsert(
				sprintf("
				INSERT INTO xxxinfo
					(title, tagline, plot, genre, director, actors, extras, productinfo, trailers, directurl, classused, cover, backdrop, createddate, updateddate)
				VALUES
					(%s, %s, COMPRESS(%s), %s, %s, %s, %s, %s, %s, %s, %s, %d, %d, NOW(), NOW())
				ON DUPLICATE KEY UPDATE
					title = %s, tagline = %s, plot = COMPRESS(%s), genre = %s, director = %s, actors = %s, extras = %s, productinfo = %s, trailers = %s, directurl = %s, classused = %s, cover = %d, backdrop = %d, updateddate = NOW()",
					$this->db->escapeString($mov['title']),
					$this->db->escapeString($mov['tagline']),
					$this->db->escapeString($mov['plot']),
					$this->db->escapeString(substr($mov['genre'], 0, 64)),
					$this->db->escapeString($mov['director']),
					$this->db->escapeString($mov['actors']),
					$this->db->escapeString($mov['extras']),
					$this->db->escapeString($mov['productinfo']),
					$this->db->escapeString($mov['trailers']),
					$this->db->escapeString($mov['directurl']),
					$this->db->escapeString($mov['classused']),
					0,
					0,
					$this->db->escapeString($mov['title']),
					$this->db->escapeString($mov['tagline']),
					$this->db->escapeString($mov['plot']),
					$this->db->escapeString(substr($mov['genre'], 0, 64)),
					$this->db->escapeString($mov['director']),
					$this->db->escapeString($mov['actors']),
					$this->db->escapeString($mov['extras']),
					$this->db->escapeString($mov['productinfo']),
					$this->db->escapeString($mov['trailers']),
					$this->db->escapeString($mov['directurl']),
					$this->db->escapeString($mov['classused']),
					0,
					0
				)
			);
			if ($xxxID !== false) {

				// BoxCover.
				if (isset($mov['cover'])) {
					$mov['cover'] = $this->releaseImage->saveImage($xxxID . '-cover', $mov['cover'], $this->imgSavePath);

				}
				// BackCover.
				if (isset($mov['backdrop'])) {
					$mov['backdrop'] = $this->releaseImage->saveImage($xxxID . '-backdrop', $mov['backdrop'], $this->imgSavePath, 1920, 1024);
				}
				$this->db->queryExec(sprintf('UPDATE xxxinfo SET cover = %d, backdrop = %d  WHERE ID = %d', $mov['cover'], $mov['backdrop'], $xxxID));
			}
		} else {
			// If xxxinfo title is found, update release with the current xxxinfo id because it was nulled before..
			$this->db->queryExec(sprintf('UPDATE releases SET xxxinfo_id = %d  WHERE ID = %d', $check['id'], $this->currentRelID));
			$xxxID = $check['id'];
		}
		if ($this->echooutput) {
			$this->c->doEcho(
				$this->c->headerOver(($xxxID !== false ? 'Added/updated movie: ' : 'Nothing to update for xxx movie: ')) .
				$this->c->primary($mov['title'])
			);
		}

		return $xxxID;
	}

	/**
	 * Process releases with no xxxinfo ID's.
	 *
	 */

	public function processXXXReleases()
	{
		// Get all releases without an IMpdo id.
		$res = $this->db->query(
			sprintf("
					SELECT r.searchname, r.ID
					FROM releases r
					WHERE r.nzbstatus = 1
					AND r.xxxinfo_id = 0
					AND r.categoryID BETWEEN 6000 AND 6040
					LIMIT %d",
				$this->movieqty
			)
		);
		$movieCount = count($res);

		if ($movieCount > 0) {
			if ($this->echooutput && $movieCount > 1) {
				$this->c->doEcho($this->c->header("Processing " . $movieCount . " XXX releases."));
			}

			// Loop over releases.
			foreach ($res as $arr) {
				// Try to get a name.
				if ($this->parseXXXSearchName($arr['searchname']) === false) {
					//We didn't find a name, so set to -2 so we don't parse again.
					$this->db->queryExec(sprintf("UPDATE releases SET xxxinfo_id = %d WHERE ID = %d", -2, $arr["ID"]));
					continue;
				} else {
					$this->currentRelID = $arr['ID'];

					$movieName = $this->currentTitle;
					$idcheck = null;
					if ($this->echooutput) {
						$this->c->doEcho($this->c->primaryOver("Looking up: ") . $this->c->headerOver($movieName), true);
						$idcheck = $this->updateXXXInfo($movieName);
					}
					if ($idcheck == false) {
						// No Release was found, set to -2 so we don't parse again.
						$this->db->queryExec(sprintf('UPDATE releases SET xxxinfo_id = %d WHERE ID = %d', -2, $arr['ID']));
						$this->c->doEcho(".", true);
						continue;
					} else {
						// Release Found, set xxxinfo_id
						$this->db->queryExec(sprintf('UPDATE releases SET xxxinfo_id = %d  WHERE ID = %d', $idcheck, $this->currentRelID));
						continue;
					}


				}
			}
		} else {
			if ($this->echooutput) {
				$this->c->doEcho($this->c->header('No xxx releases to process.'));
			}
		}
	}

	/**
	 * Parse a xxx name from a release search name.
	 *
	 * @param string $releaseName
	 *
	 * @return bool
	 */
	protected function parseXXXSearchName($releaseName)
	{
		$name = '';
		$followingList = '[^\w]((2160|1080|480|720)(p|i)|AC3D|Directors([^\w]CUT)?|DD5\.1|(DVD|BD|BR)(Rip)?|BluRay|divx|HDTV|iNTERNAL|LiMiTED|(Real\.)?Proper|RE(pack|Rip)|Sub\.?(fix|pack)|Unrated|WEB-DL|(x|H)[-._ ]?264|xvid|[Dd][Ii][Ss][Cc](\d+|\s*\d+|\.\d+)|XXX|BTS|DirFix|Trailer|WEBRiP|NFO|BONUS|(19|20)\d\d)[^\w]';

		/* Initial scan of getting a name.
		 * [\w. -]+ Gets 0-9a-z. - characters, most scene movie titles contain these chars.
		 * ie: [61420]-[FULL]-[a.b.foreignEFNet]-[ Coraline.2009.DUTCH.INTERNAL.1080p.BluRay.x264-VeDeTT ]-[21/85] - "vedett-coralien-1080p.r04" yEnc
		 */
		if (preg_match('/([^\w]{2,})?(?P<name>[\w .-]+?)' . $followingList . '/i', $releaseName, $matches)) {
			$name = $matches['name'];
		}

		// Check if we got something.
		if ($name !== '') {

			// Replace any foreign words
			$name = preg_replace('/(brazilian|chinese|croatian|danish|deutsch|dutch|english|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|latin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish)/i', ' ', $name);
			// If we still have any of the words in $followingList, remove them.
			$name = preg_replace('/' . $followingList . '/i', ' ', $name);
			// Remove periods, underscored, anything between parenthesis.
			$name = preg_replace('/\(.*?\)|[._]/i', ' ', $name);
			// Finally remove multiple spaces and trim leading spaces.
			$name = trim(preg_replace('/\s{2,}/', ' ', $name));

			// Check if the name is long enough and not just numbers and not file (d) of (d) and does not contain Episodes and any dated 00.00.00 which are site rips..
			if (strlen($name) > 5 && !preg_match('/^\d+$/', $name) && !preg_match('/(- File \d+ of \d+|\d+.\d+.\d+)/', $name) && !preg_match('/(E\d+)/', $name) && !preg_match('/\d\d\.\d\d.\d\d/', $name)) {
				$this->currentTitle = $name;

				return true;
			} else {
				$this->c->doEcho(".", false);
			}
		}

		return false;
	}

	/**
	 * Get all genres for search-filter.tpl
	 *
	 * @param bool $activeOnly
	 *
	 * @return array|null
	 */
	public function getallgenres($activeOnly = false)
	{
		$i = 0;
		$res = null;
		$ret = null;
		if ($activeOnly) {
			$res = $this->db->query("SELECT title FROM genres WHERE disabled = 0 AND type = 6000 ORDER BY title");
		} else {
			$res = $this->db->query("SELECT title FROM genres WHERE disabled = 1 AND type = 6000 ORDER BY title");
		}
		foreach ($res as $arr => $value) {
			$ret[] = $value['title'];

		}

		return $ret;
	}

	/**
	 * Get Genres for activeonly and/or an ID
	 *
	 * @param bool $activeOnly
	 * @param null $gid
	 *
	 * @return array|bool
	 */
	public function getGenres($activeOnly = false, $gid = null)
	{
		if (isset($gid)) {
			$gid = " AND ID = " . $this->db->escapeString($gid) . " ORDER BY title";
		} else {
			$gid = " ORDER BY title";
		}
		if ($activeOnly) {
			return $this->db->queryOneRow("SELECT title FROM genres WHERE disabled = 0 AND type = 6000" . $gid);
		} else {
			return $this->db->queryOneRow("SELECT title FROM genres WHERE disabled = 1 AND type = 6000" . $gid);
		}
	}

	/**
	 * Get Genre ID's Of the title
	 *
	 * @param $arr - Array or String
	 *
	 * @return string - If array .. 1,2,3,4 if string .. 1
	 */
	private function getGenreID($arr)
	{
		$ret = null;
		if (!is_array($arr)) {
			$res = $this->db->queryOneRow("SELECT ID FROM genres WHERE title = " . $this->db->escapeString($arr));
			if ($res !== false) {
				return $res["id"];
			}
		}
		foreach ($arr as $key => $value) {
			$res = $this->db->queryOneRow("SELECT ID FROM genres WHERE title = " . $this->db->escapeString($value));
			if ($res !== false) {
				$ret .= "," . $res["ID"];
			} else {
				$ret .= "," . $this->insertGenre($value);
			}
		}
		$ret = ltrim($ret, ",");

		return ($ret);
	}

	/**
	 * Inserts Genre and returns last affected row (Genre ID)
	 *
	 * @param $genre
	 *
	 * @return bool
	 */
	private function insertGenre($genre)
	{
		if (isset($genre)) {
			$res = $this->db->queryInsert(sprintf("INSERT INTO genres (title, type, disabled) VALUES (%s ,%d ,%d)", $this->db->escapeString($genre), 6000, 0));

			return $res;
		}
	}

	/**
	 * Inserts Trailer Code by Class
	 *
	 * @param $whichclass
	 * @param $res
	 *
	 * @return string
	 */
	public function insertswf($whichclass, $res)
	{
		if ($whichclass === "ade") {
			$ret = '';
			if (!empty($res)) {
				$trailers = unserialize($res);
				$ret .= "<object width='360' height='240' type='application/x-shockwave-flash' id='EmpireFlashPlayer' name='EmpireFlashPlayer' data='" . $trailers['url'] . "'>";
				$ret .= "<param name='flashvars' value= 'streamID=" . $trailers['streamid'] . "&amp;autoPlay=false&amp;BaseStreamingUrl=" . $trailers['baseurl'] . "'>";
				$ret .= "</object>";

				return ($ret);
			}
		}
		if ($whichclass === "pop") {
			$ret = '';
			if (!empty($res)) {
				$trailers = unserialize($res);
				$ret .= "<embed id='trailer' width='480' height='360'";
				$ret .= "flashvars='" . $trailers['flashvars'] . "' allowfullscreen='true' allowscriptaccess='always' quality='high' name='trailer' style='undefined'";
				$ret .= "src='" . $trailers['baseurl'] . "' type='application/x-shockwave-flash'>";

				return ($ret);
			}
		}
	}
}