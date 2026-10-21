CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Versions
 * Licensing
 * Configuration
 * Additional Plugins
 * Maintainers


INTRODUCTION
------------

A module that adds support for CKEditor 5 plugin Media Embed
to Drupal core's CKEditor.


REQUIREMENTS
------------

This module requires the CKEditor 5 Media embed plugin library. Install it with
Composer (recommended) or with the module's Drush command.

  * With Composer

    Require the library tag that matches the CKEditor 5 version Drupal core
    bundles, and map it to the directory the module loads it from. See
    `composer.libraries.json`; you should be able to copy and paste that into
    your own `composer.json`.

    ```
    composer require vardot/ckeditor5-media-embed-drupal:~47.6.2
    ```

    ```
    "extra": {
      "installer-paths": {
        "web/libraries/ckeditor5/plugins/media-embed": [
          "vardot/ckeditor5-media-embed-drupal"
        ]
      }
    }
    ```

    A distribution or recipe that pins one Drupal core minor should carry this
    require, since only it knows which CKEditor 5 version applies.

  * With Drush

    1. [Install Drush](https://www.drush.org/install).
    2. Enable the module.
    3. Run `drush ckeditor_media_embed:install`.

INSTALLATION
------------

Install the module per normal
https://www.drupal.org/documentation/install/modules-themes/modules-8
then install the plugin library as described under REQUIREMENTS above.

VERSIONS
--------

**A CKEditor 5 plugin must be built against the same CKEditor 5 version Drupal
core bundles.** Mixing minors makes the editor fail with
`ckeditor-duplicated-modules`. Read core's version from
`web/core/core.libraries.yml` (the `ckeditor5:` entry) and require the tag that
matches it:

| Drupal core | CKEditor 5 in core | Library require |
| ----------- | ------------------ | --------------- |
| 11.4.x      | 47.6.2             | `~47.6.2`       |

This is why the module only *suggests* the library rather than requiring it: it
supports several core majors, which bundle different CKEditor 5 versions, and a
single Composer constraint cannot express "whatever core bundles". The module
resolves the version at runtime instead.

LICENSING
---------

CKEditor 5 releases from 47.7.0 on that line are the **Long Term Support
edition**, which CKSource publishes under a **commercial licence only** — there
is no GPL option, so they must not be shipped in a GPL-2.0-or-later Drupal
distribution. `47.6.2` is the last GPL dual-licensed release of the 47.6 line,
and it is the one Drupal 11.4 core bundles.

CONFIGURATION
-------------

Install and enable [CKEditor media embed](https://www.drupal.org/project/ckeditor_media_embed) module.

  * WYSIWYG

    - Go to the 'Text formats and editors' configuration page:
      `/admin/config/content/formats`, and for each text format/editor combo
      where you want to embed URLs, do the following:
    - Drag and drop the 'Media Embed' button into the Active toolbar.
    - Enable the 'Convert Oembed tags to media embeds' filter.

  * Field formatter

    The field formatter allows link fields to be rendered via the configured
    oembed service provider.

    - Navigate to "Manage display" for the content type, after adding a "Link"
      field.
    - Select the "Oembed element using CKEditor Media Embed provider" format for
      the link field you wish.


MAINTAINERS
-----------

Current maintainers:
  * Jonathan DeLaigle (grndlvl) - https://www.drupal.org/u/grndlvl
