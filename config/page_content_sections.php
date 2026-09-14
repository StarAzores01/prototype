<?php

/**
 * Registry of every EC-editable text/media section across the public
 * site, keyed by page_key. Drives Ec\PageContentController's index (page
 * list) and edit (per-page form) — NOT the fallback content itself: each
 * public Blade view still carries its own literal fallback string as the
 * third argument to PageContent::get(), so a page renders correctly even
 * with zero rows in page_contents. This file only needs to stay in sync
 * with which (page_key, section_key) pairs actually appear in a
 * PageContent::get() call somewhere in resources/views/public — add a
 * row here whenever you add a new one there, or the field silently won't
 * have an edit form.
 *
 * 'global' and 'training-categories' are not real routed pages — each is
 * shared content edited once instead of once per page. 'global' covers
 * the footer/contact info that appears identically on every public page;
 * 'training-categories' covers the one photo per training area (e.g.
 * "Computer Technology") that's reused everywhere that area's card shows
 * up — currently the landing page's Courses Offered cards and the public
 * Trainings page's listing cards — so EC only ever sets it in one place.
 *
 * Every section is one of:
 *   'text'       — plain/lightly-formatted copy, edited as a <textarea>
 *   'image'      — a file upload (jpg/jpeg/png/webp/gif, same MIME
 *                  validation as every other image upload in this app)
 *   'video_link' — an external URL (YouTube/Drive/Vimeo/etc.), edited as
 *                  a plain URL <input>
 */
return [

    'global' => [
        'label' => 'Site-Wide (Logo & Footer)',
        'sections' => [
            'site_logo'      => ['label' => 'Site Logo',                 'type' => 'image'],
            'footer_tagline' => ['label' => 'Footer Tagline',            'type' => 'text'],
            'contact_address'=> ['label' => 'Address (footer + contact)','type' => 'text'],
            'contact_email'  => ['label' => 'Email (footer + contact)',  'type' => 'text'],
            'contact_phone'  => ['label' => 'Phone (footer + contact)',  'type' => 'text'],
        ],
    ],

    'landing' => [
        'label' => 'Landing Page',
        'sections' => [
            'hero_badge'          => ['label' => 'Hero Eyebrow Badge',       'type' => 'text'],
            'hero_heading'        => ['label' => 'Hero Heading',            'type' => 'text'],
            'hero_subheading'     => ['label' => 'Hero Subheading',         'type' => 'text'],
            'hero_image'          => ['label' => 'Hero Background Image',   'type' => 'image'],
            'courses_badge'       => ['label' => 'Course Highlights Badge', 'type' => 'text'],
            'courses_title'       => ['label' => 'Course Highlights Title', 'type' => 'text'],
            'courses_desc'        => ['label' => 'Course Highlights Description', 'type' => 'text'],
            'programs_badge'      => ['label' => 'Courses Offered Badge',   'type' => 'text'],
            'programs_title'      => ['label' => 'Courses Offered Title',  'type' => 'text'],
            'programs_desc'       => ['label' => 'Courses Offered Description', 'type' => 'text'],
        ],
    ],

    // One photo per training area, shared everywhere that area's card is
    // shown (landing page Courses Offered + public Trainings page listing).
    // Falls back to each card's existing plain gradient block until EC
    // uploads one — see .lp-cc-thumb-img / .card-img-photo in the two views.
    'training-categories' => [
        'label' => 'Training Category Images',
        'sections' => [
            'culinary_image'    => ['label' => 'Culinary Technology Photo',           'type' => 'image'],
            'computer_image'    => ['label' => 'Computer Technology Photo',           'type' => 'image'],
            'automotive_image'  => ['label' => 'Automotive Technology Photo',         'type' => 'image'],
            'electronics_image' => ['label' => 'Electronics Technology Photo',        'type' => 'image'],
            'apparel_image'     => ['label' => 'Apparel & Fashion Technology Photo',  'type' => 'image'],
            'mechanical_image'  => ['label' => 'Mechanical Technology Photo',         'type' => 'image'],
            'printmedia_image'  => ['label' => 'Print Media Technology Photo',        'type' => 'image'],
            'it_image'          => ['label' => 'Information Technology Photo',        'type' => 'image'],
        ],
    ],

    'about' => [
        'label' => 'About Page',
        'sections' => [
            'hero_eyebrow'   => ['label' => 'Hero Eyebrow Badge',      'type' => 'text'],
            'hero_heading'   => ['label' => 'Hero Heading',            'type' => 'text'],
            'hero_desc'      => ['label' => 'Hero Description',        'type' => 'text'],
            'system_badge'   => ['label' => '"The System" Badge',      'type' => 'text'],
            'system_title'   => ['label' => '"The System" Title',      'type' => 'text'],
            'system_body'    => ['label' => '"The System" Body Copy',  'type' => 'text'],
            'college_badge'  => ['label' => '"About the College" Badge','type' => 'text'],
            'college_title'  => ['label' => '"About the College" Title','type' => 'text'],
            'college_desc'   => ['label' => '"About the College" Intro','type' => 'text'],
            'college_body'   => ['label' => '"About the College" Body Copy', 'type' => 'text'],
            'programs_badge' => ['label' => 'Extension Programs Badge', 'type' => 'text'],
            'programs_title' => ['label' => 'Extension Programs Title', 'type' => 'text'],
            'programs_desc'  => ['label' => 'Extension Programs Description', 'type' => 'text'],
        ],
    ],

    'contact' => [
        'label' => 'Contact Page',
        'sections' => [
            'hero_eyebrow'       => ['label' => 'Hero Eyebrow Badge',        'type' => 'text'],
            'hero_heading'       => ['label' => 'Hero Heading',             'type' => 'text'],
            'hero_desc'          => ['label' => 'Hero Description',         'type' => 'text'],
            'info_section_title' => ['label' => 'Contact Info Section Title','type' => 'text'],
            'info_section_sub'   => ['label' => 'Contact Info Section Subtitle', 'type' => 'text'],
            'office_hours'       => ['label' => 'Office Hours',             'type' => 'text'],
            'website'            => ['label' => 'Website',                  'type' => 'text'],
            'facebook'           => ['label' => 'Facebook',                 'type' => 'text'],
            'message_section_title' => ['label' => '"Send a Message" Title', 'type' => 'text'],
            'message_section_sub'   => ['label' => '"Send a Message" Subtitle', 'type' => 'text'],
        ],
    ],

    'trainings-public' => [
        'label' => 'Public Trainings Page',
        'sections' => [
            'hero_eyebrow' => ['label' => 'Hero Eyebrow Badge', 'type' => 'text'],
            'hero_heading' => ['label' => 'Hero Heading',       'type' => 'text'],
            'hero_desc'    => ['label' => 'Hero Description',  'type' => 'text'],
        ],
    ],

    'choose-role' => [
        'label' => 'Choose Role Page',
        'sections' => [
            'heading'    => ['label' => 'Heading',    'type' => 'text'],
            'subheading' => ['label' => 'Subheading', 'type' => 'text'],
        ],
    ],

    'privacy' => [
        'label' => 'Privacy Policy',
        'sections' => [
            'page_heading' => ['label' => 'Page Heading', 'type' => 'text'],
            'body_html'    => ['label' => 'Policy Body',  'type' => 'text'],
        ],
    ],

    'terms' => [
        'label' => 'Terms of Use',
        'sections' => [
            'page_heading' => ['label' => 'Page Heading', 'type' => 'text'],
            'body_html'    => ['label' => 'Terms Body',   'type' => 'text'],
        ],
    ],

];
