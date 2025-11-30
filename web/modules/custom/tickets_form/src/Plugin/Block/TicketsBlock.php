<?php
/**
 * @file
 * Contains \Drupal\tickets_form\Plugin\Block\tickets_formBlock.
 */
namespace Drupal\tickets_form\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormInterface;
/**
 * Provides a 'tickets_form' block.
 *
 * @Block(
 *   id = "tickets_form_block",
 *   admin_label = @Translation("tickets_form block"),
 *   category = @Translation("Custom tickets_form block")
 * )
 */
class TicketsBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\tickets_form\Form\TicketsForm');
    return $form;
   }
}
