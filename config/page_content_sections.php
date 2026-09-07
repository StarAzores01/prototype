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
 * 'global' is not a real routed page — it's shared content that appears
 * identically on every public page's footer/contact info, edited once
 * instead of once per page.
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
