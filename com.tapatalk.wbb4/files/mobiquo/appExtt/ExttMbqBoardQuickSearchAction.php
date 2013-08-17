<?php
use wbb\action\BoardQuickSearchAction;
use wbb\data\board\Board;
use wbb\data\board\BoardCache;
use wcf\action\AbstractAction;
use wcf\data\search\SearchEditor;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\NamedUserException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\language\LanguageFactory;
use wcf\system\menu\page\PageMenu;
use wcf\system\request\LinkHandler;
use wcf\system\visitTracker\VisitTracker;
use wcf\util\HeaderUtil;
use wcf\util\StringUtil;
use wcf\system\WCF;

/**
 * ExttMbqBoardQuickSearchAction extended from BoardQuickSearchAction
 * modified method readParameters()
 * modified method execute()
 * 
 * @since  2013-7-20
 * @modified by Wu ZeTao <578014287@qq.com>
 */
class ExttMbqBoardQuickSearchAction extends BoardQuickSearchAction {
	
	/**
	 * @see	wcf\action\IAction::readParameters()
	 */
	public function readParameters() {
		//parent::readParameters();   //!!!
		
		//if (isset($_REQUEST['mode'])) $this->mode = $_REQUEST['mode'];
		$this->mode = 'unreadPosts';
		/*
		if (!in_array($this->mode, static::$validModes)) {
			throw new IllegalLinkException();
		}
		*/
		
		// get accessible board ids
		$this->boardIDs = Board::getAccessibleBoardIDs(array('canViewBoard', 'canEnterBoard', 'canReadThread'));
		foreach ($this->boardIDs as $key => $boardID) {
			$board = BoardCache::getInstance()->getBoard($boardID);
			if ($board->isIgnored() || !$board->searchable) {
				unset($this->boardIDs[$key]);
			}
		}
		if (!count($this->boardIDs)) {
			//throw new PermissionDeniedException();
			MbqError::alert('', "Can not found allowed forums.", '', MBQ_ERR_APP);
		}
	}
	
	/**
	 * @see	wcf\action\IAction::execute()
	 */
	public function execute() {
		//parent::execute();  //!!!
		
		$this->readParameters();    //!!!
		
		// set active menu item (for error messages)
		//PageMenu::getInstance()->setActiveMenuItem('wbb.header.menu.board');
		
		// build conditions
		$sql = '';
		$conditionBuilder = new PreparedStatementConditionBuilder();
		
		switch ($this->mode) {
			case 'unreadPosts':
				$conditionBuilder->add('thread.boardID IN (?)', array($this->boardIDs));
				$conditionBuilder->add('thread.lastPostTime > ?', array(VisitTracker::getInstance()->getVisitTime('com.woltlab.wbb.thread')));
				$conditionBuilder->add('thread.isDeleted = 0');
				$conditionBuilder->add('thread.isDisabled = 0');
				$conditionBuilder->add('thread.movedThreadID IS NULL');
				$conditionBuilder->add('(thread.lastPostTime > tracked_thread_visit.visitTime OR tracked_thread_visit.visitTime IS NULL)');
				$conditionBuilder->add('(thread.lastPostTime > tracked_board_visit.visitTime OR tracked_board_visit.visitTime IS NULL)');
				if (LanguageFactory::getInstance()->multilingualismEnabled() && count(WCF::getUser()->getLanguageIDs())) {
					$conditionBuilder->add('(thread.languageID IN (?) OR thread.languageID IS NULL)', array(WCF::getUser()->getLanguageIDs()));
				}
				
                $conditionBuilder->add('thread.isAnnouncement = 0');   //!!!
				
				$sql = "SELECT		thread.threadID
					FROM		wbb".WCF_N."_thread thread
					LEFT JOIN	wcf".WCF_N."_tracked_visit tracked_thread_visit
					ON		(tracked_thread_visit.objectTypeID = ".VisitTracker::getInstance()->getObjectTypeID('com.woltlab.wbb.thread')." AND tracked_thread_visit.objectID = thread.threadID AND tracked_thread_visit.userID = ".WCF::getUser()->userID.")
					LEFT JOIN	wcf".WCF_N."_tracked_visit tracked_board_visit
					ON		(tracked_board_visit.objectTypeID = ".VisitTracker::getInstance()->getObjectTypeID('com.woltlab.wbb.board')." AND tracked_board_visit.objectID = thread.boardID AND tracked_board_visit.userID = ".WCF::getUser()->userID.")
					".$conditionBuilder."
					ORDER BY	thread.lastPostTime DESC";
			    
			    $exttMbqSqlCount = "SELECT		count(thread.threadID) as totalNum
					FROM		wbb".WCF_N."_thread thread
					LEFT JOIN	wcf".WCF_N."_tracked_visit tracked_thread_visit
					ON		(tracked_thread_visit.objectTypeID = ".VisitTracker::getInstance()->getObjectTypeID('com.woltlab.wbb.thread')." AND tracked_thread_visit.objectID = thread.threadID AND tracked_thread_visit.userID = ".WCF::getUser()->userID.")
					LEFT JOIN	wcf".WCF_N."_tracked_visit tracked_board_visit
					ON		(tracked_board_visit.objectTypeID = ".VisitTracker::getInstance()->getObjectTypeID('com.woltlab.wbb.board')." AND tracked_board_visit.objectID = thread.boardID AND tracked_board_visit.userID = ".WCF::getUser()->userID.")
					".$conditionBuilder;
			break;
			
			/*
			case 'undoneThreads':
				$boardIDs = array();
				foreach ($this->boardIDs as $boardID) {
					if (BoardCache::getInstance()->getBoard($boardID)->enableMarkingAsDone) $boardIDs[] = $boardID;
				}
				if (empty($boardIDs)) {
					throw new NamedUserException(WCF::getLanguage()->getDynamicVariable('wcf.search.error.noMatches', array('query' => '')));
				}
				
				$conditionBuilder->add('thread.boardID IN (?)', array($boardIDs));
				$conditionBuilder->add('thread.isDone = 0');
				$conditionBuilder->add('thread.isDeleted = 0');
				$conditionBuilder->add('thread.isDisabled = 0');
				$conditionBuilder->add('thread.movedThreadID IS NULL');
				if (LanguageFactory::getInstance()->multilingualismEnabled() && count(WCF::getUser()->getLanguageIDs())) {
					$conditionBuilder->add('(thread.languageID IN (?) OR thread.languageID IS NULL)', array(WCF::getUser()->getLanguageIDs()));
				}
				
				$sql = "SELECT		thread.threadID
					FROM		wbb".WCF_N."_thread thread
					".$conditionBuilder."		
					ORDER BY	thread.lastPostTime DESC";
			break;
			*/
		}
		
		// build search hash
		$searchHash = StringUtil::getHash($sql);
		
		// execute query
		$matches = array();
		$statement = WCF::getDB()->prepareStatement($sql, $this->exttMbqNumPerPage, $this->exttMbqStartNum);
		$statement->execute($conditionBuilder->getParameters());
		while ($row = $statement->fetchArray()) {
			$matches[] = array('objectID' => $row['threadID'], 'objectType' => 'com.woltlab.wbb.post');
		}
		
		//get total count
		$exttMbqStatementCount = WCF::getDB()->prepareStatement($exttMbqSqlCount);
		$exttMbqStatementCount->execute($conditionBuilder->getParameters());
		while ($exttMbqRecord = $exttMbqStatementCount->fetchArray()) {
		    $exttMbqTotal = $exttMbqRecord['totalNum'];
		}
			
		// check result
		/*
		if (!count($matches)) {
			throw new NamedUserException(WCF::getLanguage()->getDynamicVariable('wcf.search.error.noMatches', array('query' => '')));
		}
		*/
		
		// save result in database
		$searchData = array(
			'packageID' => PACKAGE_ID,
			'query' => '',
			'results' => $matches,
			'additionalData' => array('com.woltlab.wbb.post' => array('findThreads' => 1)),
			'sortOrder' => 'DESC',
			'sortField' => 'time',
			'objectTypes' => array('com.woltlab.wbb.post')
		);
		$searchData = serialize($searchData);
		$search = SearchEditor::create(array(
			'userID' => (WCF::getUser()->userID ?: null),
			'searchData' => $searchData,
			'searchTime' => TIME_NOW,
			'searchType' => 'messages',
			'searchHash' => $searchHash 
		));
		
		// forward to result page
		//HeaderUtil::redirect(LinkHandler::getInstance()->getLink('SearchResult', array('id' => $search->searchID)));
		//exit;
		
		$exttMbqRetIds = array();
		foreach ($matches as $exttMbqTopicId) {
		    $exttMbqRetIds[] = $exttMbqTopicId['objectID'];
		}
		return array('total' => $exttMbqTotal, 'topicIds' => $exttMbqRetIds);
	}
}
