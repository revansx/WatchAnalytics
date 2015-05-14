<?php
/**
 * MediaWiki Extension: WatchAnalytics
 * http://www.mediawiki.org/wiki/Extension:WatchAnalytics
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * This program is distributed WITHOUT ANY WARRANTY.
 */

/**
 *
 * @file
 * @ingroup Extensions
 * @author James Montalvo
 * @licence MIT License
 */

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install this extension, put the following line in LocalSettings.php:
require_once( "$IP/extensions/WatchAnalytics/WatchAnalytics.php" );
EOT;
	exit( 1 );
}

class PageScore {

	public static $displayPageScore = true; // assume true until magic word says otherwise

	/**
	 * @var int $limit: maximum number of database rows to return
	 * @todo FIXME: who/what sets this?
	 * @example 20
	 */
	// public $limit;

	public function __construct ( Title $title ) {
	
		$this->mTitle = $title;
		$this->cssColorClasses = array(
			'excellent',
			//'good',
			'okay',
			//'warning',
			'danger',
		);
	}

	static public function noPageScore () {
		self::$displayPageScore = false;
	}

	static public function pageScoreIsEnabled () {
		return self::$displayPageScore;
	}

	/**
	 * Handles something.
	 * 
	 * @return string
	 */
	public function getWatchQuality () {
		$pwq = new PageWatchesQuery();
		return round( $pwq->getPageWatchQuality( $this->mTitle ), 1 );
	}

	public function getReviewStatus () {
		return $this->getNumReviews();
	}

	public function getNumReviews () {

		$dbr = wfGetDB( DB_SLAVE );


		$pageData = $dbr->selectRow(
			'watchlist',
			'COUNT(*) AS num_reviews',
			array(
				'wl_notificationtimestamp IS NULL',
				'wl_namespace' => $this->mTitle->getNamespace(),
				'wl_title' => $this->mTitle->getDBkey()
			),
			__METHOD__
		);

		return $pageData->num_reviews;

	}
	
	public function getScoreColor ( $score, $configVariable ) {

		$cssIndex = 4;
		$scoreArr = $GLOBALS[ $configVariable ];

		// echo $configVariable . "<br />";
		// print_r( $scoreArr );
		// echo "<br />score = $i: " . $this->cssColorClasses[ $i ] . "<br />";


		for( $i = 0; $i < count( $scoreArr ); $i++ ) { //  ) as $index => $upperBound
			if ( $score > $scoreArr[ $i ] ) {
				// echo "returning $i: " . $this->cssColorClasses[ $i ] . "<br />";
				return $this->cssColorClasses[ $i ];
			}
		}
		// echo "no loop, returning 4: " . $this->cssColorClasses[ 4 ] . "<br />";
		return $this->cssColorClasses[ count( $scoreArr ) ];

	}


	public function getPageScoreTemplate () {

		// simple explanation of what PageScores are		
		$pageScoresTooltip = wfMessage( 'watch-analytics-page-score-tooltip' )->text();

		// @FIXME: Replace with special page showing page stats
		// $pageScoresHelpPageLink = Title::makeTitle( NS_HELP, "Page Scores" )->getInternalURL();
		$pageScoresHelpPageLink = SpecialPage::getTitleFor( 'PageStatistics' )->getInternalURL( array(
			'page' => $this->mTitle->getPrefixedText()
		) );

		// when MW 1.25 is released (very soon) replace this with a mustache template
		$template = 
			"<a title='$pageScoresTooltip' id='ext-watchanalytics-pagescores' href='$pageScoresHelpPageLink'>"
				. $this->getScrutinyBadge()
				. $this->getReviewsBadge()
			. "</a>";

		return "<script type='text/template' id='ext-watchanalytics-pagescores-template'>$template</script>";

	}

	public function getBadge ( $label, $score, $color, $showLabel=true ) {

		// @todo FIXME: make the javascript apply a class to handle this, so this can just apply a class
		if ( $showLabel ) {
			$leftStyle = " style='display:inherit; border-radius: 4px 0 0 4px;'";
			$rightStyle = " style='border-radius: 0 4px 4px 0;'";
		}
		else {
			$leftStyle = "";
			$rightStyle = "";
		}

		return 
			"<div class='ext-watchanalytics-pagescores-$color'>
				<div class='ext-watchanalytics-pagescores-left'$leftStyle>
					$label
				</div>
				<div class='ext-watchanalytics-pagescores-right'$rightStyle>
					$score
				</div>
			</div>";

	}

	public function getScrutinyBadge () {
		$scrutinyScore = $this->getWatchQuality();
		$scrutinyLabel = wfMessage( 'watch-analytics-page-score-scrutiny-label' )->text();
		$scrutinyColor = $this->getScoreColor( $scrutinyScore, 'egWatchAnalyticsWatchQualityColors' );

		return $this->getBadge( $scrutinyLabel, $scrutinyScore, $scrutinyColor );
	}

	public function getReviewsBadge () {
		$reviewsScore = $this->getReviewStatus();
		$reviewsLabel = wfMessage( 'watch-analytics-page-score-reviews-label' )->text();
		$reviewsColor = $this->getScoreColor( $reviewsScore, 'egWatchAnalyticsReviewStatusColors' );

		return $this->getBadge( $reviewsLabel, $reviewsScore, $reviewsColor );
	}

}
