<?php

declare(strict_types = 1);

/**
 * @file
 * Post update functions for the Ckeditor Media Resize module.
 */

use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\FileStorage;

/**
 * Import config for dynamic resizing of images using image styles.
 */
function ckeditor_media_resize_post_update_image_style_config_import(&$sandbox) {
  $path = \Drupal::service('extension.list.module')->getPath('ckeditor_media_resize');
  $path .= '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY;
  $source = new FileStorage($path);
  /** @var \SplFileInfo $file */
  foreach (new \DirectoryIterator($path) as $file) {
    if ($file->isFile()) {
      /** @var \Drupal\Core\Config\StorageInterface $active_storage */
      $active_storage = \Drupal::service('config.storage');
      $config_name = $file->getBasename('.yml');
      $active_storage->write($config_name, $source->read($config_name));
    }
  }
}

/**
 * Set the dependencies on the custom image styles.
 */
function ckeditor_media_resize_post_update_config_dependencies() {
  $config_factory = \Drupal::configFactory();

  $config_names = [
    'image.style.cke_media_resize_large',
    'image.style.cke_media_resize_medium',
    'image.style.cke_media_resize_small',
    'image.style.cke_media_resize_xl',
  ];
  $dependencies['enforced']['module'][] = 'ckeditor_media_resize';

  // Fix the configurations if the dependency is not set.
  foreach ($config_names as $config_name) {
    $config = $config_factory->getEditable($config_name);
    if ($config) {
      $config->set('dependencies', $dependencies);
      $config->save(TRUE);
    }
  }
}
