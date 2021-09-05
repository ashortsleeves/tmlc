import React from 'react';
import ReactDOM from 'react-dom';

import { TutorialsSlider, TutorialsList } from '@wpmudev/shared-tutorials';

var categoryId = 11239;		// Snapshot
var tutorialsUtmTags = 'utm_source=snapshot&utm_medium=plugin&utm_campaign=snapshot_tutorial_read_article';
var viewAllLink = 'https://wpmudev.com/blog/tutorials/tutorial-category/snapshot-pro/?' + tutorialsUtmTags;

function closeTutorialsSlider() {
	jQuery(window).trigger('snapshot:close_tutorials_slider');
}

var tutorialsSliderContainer = document.getElementById('snapshot-tutorials-slider');
if (tutorialsSliderContainer) {
	ReactDOM.render(
		<TutorialsSlider
			category={ categoryId }
			title={ snapshot_messages.tutorials }
			viewAll={ viewAllLink }
			onCloseClick={ closeTutorialsSlider }
			postLinkParams={ tutorialsUtmTags }
		/>,
		tutorialsSliderContainer
	);
}

var tutorialsListContainer = document.getElementById('snapshot-tutorials-list');
if (tutorialsListContainer) {
	ReactDOM.render(
		<TutorialsList
			category={ categoryId }
			title={ snapshot_messages.snapshot_tutorials }
			postLinkParams={ tutorialsUtmTags }
		/>,
		tutorialsListContainer
	);
}
