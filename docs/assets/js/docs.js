"use strict";


/* ====== Define JS Constants ====== */
const sidebarToggler = document.getElementById('docs-sidebar-toggler');
const sidebar = document.getElementById('docs-sidebar');
const sidebarLinks = document.querySelectorAll('#docs-sidebar .scrollto');



/* ===== Responsive Sidebar ====== */

window.onload = function() {
    responsiveSidebar();
};

window.onresize = function() {
    responsiveSidebar();
};


function responsiveSidebar() {
    let w = window.innerWidth;
	if(w >= 1200) {
	    // if larger
		sidebar.classList.remove('sidebar-hidden');
		sidebar.classList.add('sidebar-visible');

	} else {
	    // if smaller
	    sidebar.classList.remove('sidebar-visible');
		sidebar.classList.add('sidebar-hidden');
	}
};

sidebarToggler.addEventListener('click', () => {
	if (sidebar.classList.contains('sidebar-visible')) {
		console.log('visible');
		sidebar.classList.remove('sidebar-visible');
		sidebar.classList.add('sidebar-hidden');

	} else {
		console.log('hidden');
		sidebar.classList.remove('sidebar-hidden');
		sidebar.classList.add('sidebar-visible');
	}
});


/**
 * Smooth scrolling.
 *
 * Note: You need to include smoothscroll.min.js (smooth scroll behavior polyfill) on the page
 * to cover some browsers.
 *
 * @ref https://github.com/iamdustan/smoothscroll
 */
sidebarLinks.forEach((sidebarLink) => {

	sidebarLink.addEventListener('click', (e) => {

		e.preventDefault();

		var target = sidebarLink.getAttribute("href").replace('#', '');

        document.getElementById(target).scrollIntoView({ behavior: 'smooth' });

        // collapse sidebar after clicking
		if (sidebar.classList.contains('sidebar-visible') && window.innerWidth < 1200){

			sidebar.classList.remove('sidebar-visible');
		    sidebar.classList.add('sidebar-hidden');
		}

    });

});


/**
 * Initialise highlight js on <pre></code> blocks.
 *
 * @ref https://highlightjs.readthedocs.io/en/latest/index.html
 */
hljs.initHighlighting();


/**
 * Gumshoe SrollSpy
 *
 * @ref https://github.com/cferdinandi/gumshoe
 */
var spy = new Gumshoe('#docs-nav a', {
	offset: 69 // sticky header height
});
