<?php

namespace Drupal\tickets_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;


/**
 *
 * @see \Drupal\Core\Form\FormBase
 */
class TicketsForm extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    static $count = 0;
    $count++;
    return 'tickets_form_form_'.$count;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['#cache'] = ['max-age' => 3600];
    $wrapper_id = Html::getUniqueId('tickets-form');
    $form['#prefix'] = '<div id="' . $wrapper_id . '"><div class="close"></div>';
    $form['#suffix'] = '</div>';
    $values = $form_state->getValues();
    $ajax = [
      'callback' => [$this, 'ajaxRefresh'],
      'wrapper' => $wrapper_id,
      'progress' => ['type' => 'fullscreen'],
    ];
    $event = (!empty($values['events'])) ? $values['events'] : '';
    $date = (!empty($values['dates'])) ? $values['dates'] : '';
    $time = (!empty($values['times'])) ? $values['times'] : '';
    $form['events'] = [
      '#type' => 'select',
      '#title' => $this->t('Select event or movie'),
      '#options' => $this->getEvents(),
      '#required' => TRUE,
      //'#default_value' => $event,
      '#ajax' => $ajax,
    ];
    $dates = $this->getDates($event);
    $form['dates'] = [
      '#type' => 'select',
      '#title' => $this->t('Select date'),
      '#options' => $dates,
      '#required' => TRUE,
      '#disabled' => empty($dates),
      '#ajax' => $ajax,
    ];
    $times = $this->getTimes($event, $date);
    $form['times'] = [
      '#type' => 'select',
      '#title' => $this->t('Select time'),
      '#options' => $times,
      '#required' => TRUE,
      '#disabled' => empty($times),
      '#ajax' => $ajax,
    ];

    if (!empty($times) && !empty($time) && array_key_exists($time, $times)) {
      $form['times']['#default_value'] = $time;
    }

    if (!empty($time)) {
      $link = $this->getLink($time);
      if (empty($link) && !empty($event)){
        $link = $this->getStaticLink($event);
      }
      $form['tickets_ink'] = [
        '#type' => 'markup',
        '#markup' => '<div class="ticket-button"><a href="' . $link . '">' . $this->t('Buy tickets') . '</a></div>',
      ];
    }elseif(!empty($event)){
      $link = $this->getStaticLink($event);//field_order_tickets
      $form['tickets_ink'] = [
        '#type' => 'markup',
        '#markup' => '<div class="ticket-button"><a href="' . $link . '">' . $this->t('Buy tickets') . '</a></div>',
      ];
    } else {
      $form['tickets_ink'] = [
        '#type' => 'markup',
        '#markup' => '<div class="ticket-button disabled">' . $this->t('Buy tickets') . '</div>',
      ];
    }


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $form_state->setRebuild(TRUE);
    return $form;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state)
  {
    $form_state->setRebuild(TRUE);
    return $form;
  }

  private function getEvents()
  {
    
    $entityStoragePg = \Drupal::entityTypeManager()->getStorage('paragraph');
    $now = new DrupalDateTime('now');
    $queryPg = $entityStoragePg->getQuery();
    $queryPg->condition('type', 'movie_date'); 
    $queryPg->condition('field_date', $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '>=');
    $queryPg->accessCheck(FALSE);
    $screening_dates = $queryPg->execute();
    if (empty($screening_dates)) $screening_dates = [1];
    $result = [];
    $entityStorage = \Drupal::entityTypeManager()->getStorage('node');
    $query = $entityStorage->getQuery();
    $query->condition('type', 'movie');
    $query->condition('status', '1');
    $query->condition('field_screening_date', $screening_dates, 'IN');
    $query->accessCheck(FALSE);
    $nids = $query->execute();
    if (empty($nids)) return [];
    $nodes = $entityStorage->loadMultiple($nids);
   /* foreach ($nodes as $node) {
      $dates = $node->get('field_screening_date')->referencedEntities();
      foreach ($dates as $dateRow) {
        $date = $dateRow->get('field_date')->getString();
        $dateObj = new DrupalDateTime($date, 'UTC');
        if (!empty($date) && $dateObj->getTimestamp() > time()) {
          $result[$node->id()] = $node->label();
          continue;
        }
      }

    }*/
    foreach ($nodes as $node) {
      $result[$node->id()] = $node->label();
    }
    return $result;
  }

  private function getDates($event_id)
  {
    $result = [];
    if (empty($event_id)) return $result;
    $event = \Drupal::entityTypeManager()->getStorage('node')->load($event_id);
    $dates = $event->get('field_screening_date')->referencedEntities();
    foreach ($dates as $dateRow) {
      $date = $dateRow->get('field_date')->getString();
      if (!empty($date) && strtotime($date) > time()) {
        $dateObj = new DrupalDateTime($date, 'UTC');
        $formatedDate = \Drupal::service('date.formatter')->format($dateObj->getTimestamp(), 'short_date');
        if (!in_array($formatedDate, $result))
          $result[$dateRow->id()] = $formatedDate;
      }
    }
    return $result;
  }

  private function getTimes($event_id, $date_id)
  {
    $correct = false;
    $result = [];
    if (empty($event_id) || empty($date_id)) return $result;
    $event = \Drupal::entityTypeManager()->getStorage('node')->load($event_id);
    $selectedDatePg = \Drupal::entityTypeManager()->getStorage('paragraph')->load($date_id);
    $selectedDate = $selectedDatePg->get('field_date')->getString();
    $selectedDateObj = new DrupalDateTime($selectedDate, 'UTC');
    $selectedTimeStamp = $selectedDateObj->getTimestamp();
    $selectedDateFormated = \Drupal::service('date.formatter')->format($selectedTimeStamp, 'short_date');
    $result[$selectedDatePg->id()] = \Drupal::service('date.formatter')->format($selectedTimeStamp, 'only_time');
    $dates = $event->get('field_screening_date')->referencedEntities();
    foreach ($dates as $dateRow) {
      $date = $dateRow->get('field_date')->getString();
      if (!empty($date) && $dateRow->id() != $selectedDatePg->id()) {
        $dateObj = new DrupalDateTime($date, 'UTC');
        $dateFormated = \Drupal::service('date.formatter')->format($dateObj->getTimestamp(), 'short_date');
        if ($dateFormated == $selectedDateFormated)
          $result[$dateRow->id()] = \Drupal::service('date.formatter')->format($dateObj->getTimestamp(), 'only_time');;
      } elseif ($dateRow->id() == $selectedDatePg->id()) {
        $correct = true;
      }
    }
    if (!$correct) $result = [];
    return $result;
  }

  private function getLink($time_id)
  {
    $result = '';
    if (empty($time_id)) return $result;
    $selectedDatePg = \Drupal::entityTypeManager()->getStorage('paragraph')->load($time_id);
    if (!empty($selectedDatePg)) {
      $link = $selectedDatePg->get('field_link_by_date')->getString();
      if (!empty($link)) $result = $link;
    }
    return $result;
  }
  private function getStaticLink($event_id)
  {
    $result = '';
    if (empty($event_id)) return $result;
    $selectedEvent = \Drupal::entityTypeManager()->getStorage('node')->load($event_id);
    if (!empty($selectedEvent)) {
      $link = $selectedEvent->get('field_order_tickets')->getString();
      if (!empty($link)) $result = $link;
    }
    return $result;
  }

}