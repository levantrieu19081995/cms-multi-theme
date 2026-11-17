<?php

/*
|--------------------------------------------------------------------------
| Projects (deployments) of this CMS and the public theme of each
|--------------------------------------------------------------------------
| A project owns its content, its publication history and its social accounts: they are stored with its `project_key`.
| Read the current project only through Modules\CmsBackend\Support\ProjectContext, never from a theme or from a title.
|
|  - Production: set CMS_PROJECT_KEY (process environment / .env) to one key below. The project is then fixed, the public
|    theme follows it and cannot be changed in the administration.
|  - Local (all themes, CMS_PROJECT_KEY empty): the project follows the public theme chosen in the administration, so
|    switching the theme switches project, theme, posts, history and accounts together.
*/

return [
    'key' => env('CMS_PROJECT_KEY'),

    // Project used in local mode when the stored theme belongs to no project below.
    'default' => 'khuyen-nong',

    // project key => label and the key of its public theme (see screens.php). `other_themes` = further themes of the same project
    // (e.g. a redesign being built): they belong to the project too, so choosing one never switches to another project's data.
    'projects' => [
        'nam-binh-duong' => ['label' => 'Nam Bình Dương', 'theme' => 'nam-binh-duong'],
        'ban-de-an' => ['label' => 'Ban Đề án', 'theme' => 'ban-de-an'],
        // A site of its own (theme, login design, guides and permission manifest `bandean-v2`) that works on the DATA of Ban Đề án:
        // `data_project` names the project whose posts, publication history and social accounts it reads and writes
        // (ProjectContext::dataKey()). Without `data_project` a project owns its data.
        'bandean-v2' => ['label' => 'Ban Đề án v2', 'theme' => 'bandean-v2', 'data_project' => 'ban-de-an'],
        'khuyen-nong' => ['label' => 'Khuyến nông', 'theme' => 'default'],
    ],
];
