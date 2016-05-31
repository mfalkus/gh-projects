<?php
/**
 * This file acts as a rudimentary template file for the GitHub projects plugin
 * To use:
 *  - Copy the file to your theme directory, keeping the filename
 *  - Update the functions as you'd like!
 *
 * Note that only this file OR your custom one will be loaded, so you need to
 * make sure each function is provided.
 */

/**
 * Preamble before we get to the projects. This is the place to open your table
 * or unordered list elements.
 *
 * @return HTML output before project list
 */
function ghprojects_pre_list() {
    return '<ul class="project-list">';
}


/**
 * Handle printing an individual project row
 *
 * @return HTML for a single project
 */
function ghprojects_list_project($project) {
    return '<li class="project-item">'
        . '<h3 class="project-title"><a href="' . $project->html_url . '">'
            . $project->name
        . '</a> '
        . '<small class="date">'
            . date('d M Y', strtotime($project->pushed_at))
        . '</small>'
        . '</h3>'
        . '<p>' . $project->description . '</p>';
}


/**
 * End our project list, close up ul/table etc
 *
 * @return HTML to close out list
 */
function ghprojects_post_list() {
    return '</ul>';
}
