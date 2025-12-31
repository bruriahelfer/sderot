<?php

namespace Drupal\all_in_one_accessibility\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;

/**
 * Provide settings page for adding CSS/JS before the end of body tag.
 */
class UseridForm extends ConfigFormBase {
  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new UseridForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    RequestStack $request_stack,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'all_in_one_accessibility';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['all_in_one_accessibility.userid.settings'];
  }

  /**
   * Implements FormBuilder::buildForm.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?Request $request = NULL) {
    $request = $this->requestStack->getCurrentRequest();
    $aioa_host_info = $request->getHost();

    $allinone_userid = $this->config('all_in_one_accessibility.userid.settings')->get();

    if (!isset($allinone_userid['userid'])) {
      $allinone_userid['userid'] = "";
    }

    $url = "https://www.skynettechnologies.com/add-ons/license-api.php?";
    $postdata['token'] = $allinone_userid['userid'];
    $postdata['SERVER_NAME'] = parse_url(Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(), PHP_URL_HOST);
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, TRUE);
    $resp = curl_exec($curl);
    // Close the cURL resource.
    curl_close($curl);
    $resp = json_decode($resp);

    $form['allinone']['userid'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('License key required for full version:'),
      '#default_value' => $allinone_userid['userid'] ?? '',
      '#description'   => empty($allinone_userid['userid']) ? $this->t('Please <a href="@url" target="_blank">Upgrade</a> to paid version of All in One Accessibility®.', ['@url' => 'https://www.skynettechnologies.com/add-ons/product/all-in-one-accessibility-pro/?attribute_package-name=Medium+Site+%28100K+Page+Views%2Fmo%29&attribute_subscription=1+Year&utm_source=' . $aioa_host_info . '&utm_medium=drupal-module&utm_campaign=trial-subscription']) : '',
      '#rows'          => 10,
    ];

    $form['allinone']['nofreeversion'] = [
      '#type'          => 'hidden',
      '#default_value' => 1,
    ];

    if (!isset($allinone_userid['colorcode'])) {
      $allinone_userid['colorcode'] = "";
    }
    $form['allinone']['colorcode'] = [
      '#type'          => 'color',
      '#title'         => $this->t('Pick a color for widget:'),
      '#default_value' => $allinone_userid['colorcode'] ?? '',
      '#rows'          => 10,
    ];

    $options = [
      '0' => $this->t('Fix Position'),
      '1' => $this->t('Custom Position'),
    ];

    if (!isset($allinone_userid['is_widget_custom_position'])) {
      $allinone_userid['is_widget_custom_position'] = "";
    }
    $form['allinone']['is_widget_custom_position'] = [
      '#type'            => 'radios',
      '#title'           => $this->t('Select Position Type:'),
      '#options'         => $options,
      '#default_value'   => $allinone_userid['is_widget_custom_position'] ?? '0',
    ];

    // Fix Position Radio Buttons.
    $form['allinone']['fix_position_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Fixed Position Options'),
      '#states' => [
        'visible' => [
          ':input[name="is_widget_custom_position"]' => ['value' => '0'],
        ],
      ],
    ];

    $options = [
      'bottom_right'  => $this->t('Bottom Right'),
      'bottom_left'   => $this->t('Bottom Left'),
      'bottom_center' => $this->t('Bottom Center'),
      'middle_left'   => $this->t('Middle Left'),
      'middle_right'  => $this->t('Middle Right'),
      'top_left'      => $this->t('Top Left'),
      'top_center'    => $this->t('Top Center'),
      'top_right'     => $this->t('Top Right'),
    ];

    if (!isset($allinone_userid['position'])) {
      $allinone_userid['position'] = "bottom_right";
    }
    $form['allinone']['fix_position_options']['position'] = [
      '#type'          => 'radios',
      '#options'       => $options,
      '#default_value' => $allinone_userid['position'] ?? 'bottom_right',
    ];

    $form['allinone']['custom_position_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Custom Postion Options'),
      '#states' => [
        'visible' => [
          ':input[name="is_widget_custom_position"]' => ['value' => '1'],
        ],
      ],
    ];

    $form['allinone']['custom_position_options']['horizontal'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['horizontal-container']],
    ];

    if (!isset($allinone_userid['widget_position_left'])) {
      $allinone_userid['widget_position_left'] = "";
    }
    $form['allinone']['custom_position_options']['horizontal']['widget_position_left'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Horizontal (px)'),
      '#size' => 10,
      '#default_value' => $allinone_userid['widget_position_left'],
      '#attributes' => ['placeholder' => $this->t('Enter pixels')],
    ];

    if (!isset($allinone_userid['widget_position_top'])) {
      $allinone_userid['widget_position_top'] = "";
    }
    $form['allinone']['custom_position_options']['horizontal']['widget_position_top'] = [
      '#type' => 'select',
      '#title' => $this->t('Position'),
      '#default_value' => $allinone_userid['widget_position_top'],
      '#options' => [
        'left' => $this->t('to the Left'),
        'right' => $this->t('to the Right'),

      ],
      '#empty_option' => $this->t('- Select -'),
    ];

    $form['allinone']['custom_position_options']['vertical'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['vertical-container']],
    ];

    if (!isset($allinone_userid['widget_position_right'])) {
      $allinone_userid['widget_position_right'] = "";
    }
    $form['allinone']['custom_position_options']['vertical']['widget_position_right'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vertical (px)'),
      '#size' => 10,
      '#default_value' => $allinone_userid['widget_position_right'],
      '#attributes' => ['placeholder' => $this->t('Enter pixels')],
    ];

    if (!isset($allinone_userid['widget_position_bottom'])) {
      $allinone_userid['widget_position_bottom'] = "";
    }
    $form['allinone']['custom_position_options']['vertical']['widget_position_bottom'] = [
      '#type' => 'select',
      '#title' => $this->t('Position'),
      '#default_value' => $allinone_userid['widget_position_bottom'],
      '#options' => [
        'top' => $this->t('to the Top'),
        'bottom' => $this->t('to the Bottom'),
      ],
      '#empty_option' => $this->t('- Select -'),
    ];

    if (!isset($allinone_userid['statement_link'])) {
      $allinone_userid['statement_link'] = "";
    }
    if (isset($allinone_userid['userid']) && !empty(trim($allinone_userid['userid']))) {
      $form['allinone']['statement_link'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Statement Link:'),
        '#default_value' => $allinone_userid['statement_link'] ?? '',
        '#description' => $this->t('Enter the URL for the statement link.'),
        '#placeholder' => $this->t('https://www.yourwebsite.link/accessibility-statement'),
        '#weight' => 50,
      ];
    }

    $options3 = [
      'regularsize' => $this->t('Regular Size'),
      'oversize' => $this->t('Oversize'),
    ];

    $form['allinone']['widget_size'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select Widget Size:'),
      '#options' => $options3,
      '#description' => $this->t('It only works on desktop view.'),
      '#default_value' => $allinone_userid['widget_size'] ?? 'regularsize',
    ];

    $options1 = [
      'aioa-icon-type-1' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-1.svg" width="65" height="65" />'),
      'aioa-icon-type-2' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-2.svg" width="65" height="65" />'),
      'aioa-icon-type-3' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-3.svg" width="65" height="65" />'),
      'aioa-icon-type-4' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-4.svg" width="65" height="65" />'),
      'aioa-icon-type-5' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-5.svg" width="65" height="65" />'),
      'aioa-icon-type-6' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-6.svg" width="65" height="65" />'),
      'aioa-icon-type-7' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-7.svg" width="65" height="65" />'),
      'aioa-icon-type-8' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-8.svg" width="65" height="65" />'),
      'aioa-icon-type-9' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-9.svg" width="65" height="65" />'),
      'aioa-icon-type-10' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-10.svg" width="65" height="65" />'),
      'aioa-icon-type-11' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-11.svg" width="65" height="65" />'),
      'aioa-icon-type-12' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-12.svg" width="65" height="65" />'),
      'aioa-icon-type-13' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-13.svg" width="65" height="65" />'),
      'aioa-icon-type-14' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-14.svg" width="65" height="65" />'),
      'aioa-icon-type-15' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-15.svg" width="65" height="65" />'),
      'aioa-icon-type-16' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-16.svg" width="65" height="65" />'),
      'aioa-icon-type-17' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-17.svg" width="65" height="65" />'),
      'aioa-icon-type-18' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-18.svg" width="65" height="65" />'),
      'aioa-icon-type-19' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-19.svg" width="65" height="65" />'),
      'aioa-icon-type-20' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-20.svg" width="65" height="65" />'),
      'aioa-icon-type-21' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-21.svg" width="65" height="65" />'),
      'aioa-icon-type-22' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-22.svg" width="65" height="65" />'),
      'aioa-icon-type-23' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-23.svg" width="65" height="65" />'),
      'aioa-icon-type-24' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-24.svg" width="65" height="65" />'),
      'aioa-icon-type-25' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-25.svg" width="65" height="65" />'),
      'aioa-icon-type-26' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-26.svg" width="65" height="65" />'),
      'aioa-icon-type-27' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-27.svg" width="65" height="65" />'),
      'aioa-icon-type-28' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-28.svg" width="65" height="65" />'),
      'aioa-icon-type-29' => $this->t('<img class="aioaicontype" src="https://www.skynettechnologies.com/sites/default/files/aioa-icon-type-29.svg" width="65" height="65" />'),
    ];

    if (!isset($allinone_userid['aioa_icon_type'])) {
      $allinone_userid['aioa_icon_type'] = "aioa-icon-type-1";
    }
    $form['allinone']['aioa_icon_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select Icon Type:'),
      '#options' => $options1,
      '#default_value' => $allinone_userid['aioa_icon_type'] ?? 'aioa-icon-type-1',
    ];

    $form['allinone']['is_widget_custom_size'] = [
      '#type' => 'radios',
      '#title' => $this->t('Widget Icon Size for Desktop:'),
      '#options' => [
        '0' => $this->t('Fixed Icon Size'),
        '1' => $this->t('Custom Icon Size'),
      ],
      '#default_value' => $allinone_userid['is_widget_custom_size'] ?? '0',
    ];

    $options2 = [
      'aioa-big-icon' => $this->t('<img class="aioaiconsize" src="@url" width="75" height="75" />', [
        '@url' => 'https://www.skynettechnologies.com/sites/default/files/' . $allinone_userid['aioa_icon_type'] . '.svg',
      ]),
      'aioa-medium-icon' => $this->t('<img class="aioaiconsize" src="@url" width="65" height="65" />', [
        '@url' => 'https://www.skynettechnologies.com/sites/default/files/' . $allinone_userid['aioa_icon_type'] . '.svg',
      ]),
      'aioa-default-icon' => $this->t('<img class="aioaiconsize" src="@url" width="55" height="55" />', [
        '@url' => 'https://www.skynettechnologies.com/sites/default/files/' . $allinone_userid['aioa_icon_type'] . '.svg',
      ]),
      'aioa-small-icon' => $this->t('<img class="aioaiconsize" src="@url" width="45" height="45" />', [
        '@url' => 'https://www.skynettechnologies.com/sites/default/files/' . $allinone_userid['aioa_icon_type'] . '.svg',
      ]),
      'aioa-extra-small-icon' => $this->t('<img class="aioaiconsize" src="@url" width="35" height="35" />', [
        '@url' => 'https://www.skynettechnologies.com/sites/default/files/' . $allinone_userid['aioa_icon_type'] . '.svg',
      ]),
    ];

    $form['allinone']['aioa_icon_size'] = [
      '#type' => 'radios',
      '#title' => $this->t('Fixed Icon Size:'),
      '#options' => $options2,
      '#default_value' => $allinone_userid['aioa_icon_size'] ?? 'aioa-medium-icon',
      '#states' => [
        'visible' => [
          ':input[name="is_widget_custom_size"]' => ['value' => '0'],
        ],
      ],
      '#description' => $this->t('<script>
            const sizeOptionsImg = document.querySelectorAll(".aioaiconsize");
            const typeOptions = document.querySelectorAll("input[name=\'aioa_icon_type\']");
            typeOptions.forEach(option => {
                option.addEventListener("change", (event) => {
                    sizeOptionsImg.forEach(option2 => {
                        var ico_type = document.querySelector("input[name=\'aioa_icon_type\']:checked").value;
                        option2.setAttribute("src", "https://www.skynettechnologies.com/sites/default/files/" + ico_type + ".svg");
                    });
                });
            });
        </script>
        <style>
            /* Radio Button Css */
            #edit-aioa-icon-type input,
            #edit-aioa-icon-size input,
            #edit-aioa-icon-sizes input,
            #edit-fixed-widget-icon-size input {
                position: absolute;
                opacity: 0;
            }
            #edit-aioa-icon-type .form-item,
            #edit-aioa-icon-size .form-item,
            #edit-aioa-icon-sizes .form-item,
            #edit-fixed-widget-icon-size .form-item {
                margin-left: 0;
                position: relative;
            }
            #edit-aioa-icon-type input[type=radio]+label,
            #edit-aioa-icon-size input[type=radio]+label,
            #edit-aioa-icon-sizes input[type=radio]+label,
            #edit-fixed-widget-icon-size input[type=radio]+label {
                width: 130px;
                height: 130px;
                padding: 10px !important;
                text-align: center;
                background-color: #000000;
                outline: 4px solid #f7f9ff;
                outline-offset: -4px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-left: 12px;
                margin-right: 12px;
            }
            #edit-aioa-icon-type input[type=radio]:checked+label,
            #edit-aioa-icon-size input[type=radio]:checked+label,
            #edit-aioa-icon-sizes input[type=radio]:checked+label,
            #edit-fixed-widget-icon-size input[type=radio]:checked+label {
                outline-color: #80c944;
                position: relative;
            }
            #edit-aioa-icon-type input[type=radio]:checked+label::before,
            #edit-aioa-icon-size input[type=radio]:checked+label::before,
            #edit-aioa-icon-sizes input[type=radio]:checked+label::before,
            #edit-fixed-widget-icon-size input[type=radio]:checked+label::before {
                content: "";
                width: 20px;
                height: 20px;
                position: absolute;
                left: auto;
                right: -4px;
                top: -4px;
                background: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 25 25\' class=\'aioa-feature-on\'%3E%3Cg%3E%3Ccircle fill=\'%2343A047\' cx=\'12.5\' cy=\'12.5\' r=\'12\'%3E%3C/circle%3E%3Cpath fill=\'%23FFFFFF\' d=\'M12.5,1C18.9,1,24,6.1,24,12.5S18.9,24,12.5,24S1,18.9,1,12.5S6.1,1,12.5,1 M12.5,0C5.6,0,0,5.6,0,12.5S5.6,25,12.5,25S25,19.4,25,12.5S19.4,0,12.5,0L12.5,0z\'%3E%3C/path%3E%3C/g%3E%3Cpolygon fill=\'%23FFFFFF\' points=\'9.8,19.4 9.8,19.4 9.8,19.4 4.4,13.9 7.1,11.1 9.8,13.9 17.9,5.6 20.5,8.4 \'%3E%3C/polygon%3E%3C/svg%3E") no-repeat center center/contain !important;
                border: none;
            }
            /* IMAGE STYLES */
            #edit-aioa-icon-type label>img,
            #edit-aioa-icon-size label>img,
            #edit-aioa-icon-sizes label>img{
                cursor: pointer;
            }
            #edit-aioa-icon-type label,
            #edit-aioa-icon-size label,
            #edit-aioa-icon-sizes label {
                display: flex;
                justify-content: center;
                height: 90px;
                width: 90px;
                border: 2px solid gray;
                border-radius: 3px;
            }
    
            #edit-aioa-icon-type,
            #edit-aioa-icon-size,
            #edit-aioa-icon-sizes {
                display: flex;
                flex-wrap: wrap;
                margin-left: -15px;
                margin-right: -15px;
            }
            #edit-position {
                max-width: 520px;
                display: flex;
                flex-wrap: wrap;
            }
            #edit-position .form-item {
                width: 33.33333%;
            }
            #edit-custom-position-options .fieldset__wrapper {
    margin-bottom: 0;
  }
  #edit-custom-position-options .fieldset__wrapper .js-form-wrapper {
    display: flex;
    flex-wrap: wrap;
    column-gap: 20px;
  }
  #edit-custom-position-options .fieldset__wrapper .js-form-wrapper .js-form-item {
    min-width: 250px;
    margin-top: 0;
  }
  #edit-custom-position-options .fieldset__wrapper .js-form-wrapper .js-form-item input,
  #edit-custom-position-options .fieldset__wrapper .js-form-wrapper .js-form-item select {
    width: 100%;
  }

  .all-in-one-accessibility {
  font-size: 18px; 
}

.all-in-one-accessibility label,
.all-in-one-accessibility input,
.all-in-one-accessibility select,
.all-in-one-accessibility legend span, .all-in-one-accessibility textarea {
  font-size: 18px; /* Applies to form fields and labels */
}
        </style>'),
    ];
    $form['allinone']['widget_icon_size_custom'] = [
      '#type' => 'number',
      '#title' => $this->t('Custom Widget Icon Size for Desktop (px):'),
      '#description' => $this->t('20-150px are recommended values.'),
      '#placeholder' => $this->t('20'),
      '#default_value' => $allinone_userid['widget_icon_size_custom'] ?? '',
      '#size' => 10,
    // Set minimum value.
      '#min' => 20,
    // Set maximum value.
      '#max' => 150,
      '#states' => [
        'visible' => [
          ':input[name="is_widget_custom_size"]' => ['value' => '1'],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements FormBuilder::submitForm().
   *
   * Serialize the user's settings and save it to the Drupal's config Table.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $url = "https://www.skynettechnologies.com/add-ons/license-api.php?";
    $postdata['token'] = $values['userid'];
    $postdata['SERVER_NAME'] = parse_url(Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(), PHP_URL_HOST);

    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => TRUE,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => $postdata,
    ]);
    $resp = curl_exec($curl);
    // Close the cURL resource.
    curl_close($curl);
    // Fixed: pass $resp instead of $curl.
    $resp = json_decode($resp);

    if (empty($resp->accessibilityloader) && !empty(trim($values['userid']))) {
      $values['userid'] = "";
      $values['statement_link'] = (!empty($values['statement_link']) ? $values['statement_link'] : "");
      $values['widget_size'] = (!empty($values['widget_size']) ? $values['widget_size'] : "regularsize");
      $values['colorcode'] = (!empty($values['colorcode']) ? $values['colorcode'] : "#420083");
      $values['aioa_icon_type'] = (!empty($values['aioa_icon_type']) ? $values['aioa_icon_type'] : "aioa-icon-type-1");
      $values['aioa_icon_size'] = (!empty($values['aioa_icon_size']) ? $values['aioa_icon_size'] : "aioa-medium-icon");
      $values['widget_icon_size_mobile'] = (!empty($values['aioa_icon_sizes']) ? $values['aioa_icon_sizes'] : "aioa-medium-icon");
      $values['is_widget_custom_size_mobile'] = (!empty($values['is_widget_custom_size_mobile']) ? $values['is_widget_custom_size_mobile'] : "0");
      $values['is_widget_custom_size'] = (!empty($values['is_widget_custom_size']) ? $values['is_widget_custom_size'] : "0");
      $values['widget_icon_size_custom_mobile'] = (!empty($values['widget_icon_size_custom_mobile']) ? $values['widget_icon_size_custom_mobile'] : "");
      $values['widget_icon_size_custom'] = (!empty($values['widget_icon_size_custom']) ? $values['widget_icon_size_custom'] : "");
      $values['is_widget_custom_position'] = (!empty($values['is_widget_custom_position']) ? $values['is_widget_custom_position'] : "0");
      $values['widget_position_left'] = ((!empty($values['widget_position_top']) && $values['widget_position_top'] == "left") ? $values['widget_position_left'] : "");
      $values['widget_position_top'] = ((!empty($values['widget_position_bottom']) && $values['widget_position_bottom'] == "top") ? $values['widget_position_right'] : "");
      $values['widget_position_right'] = ((!empty($values['widget_position_top']) && $values['widget_position_top'] == "right") ? $values['widget_position_left'] : "");
      $values['widget_position_bottom'] = ((!empty($values['widget_position_bottom']) && $values['widget_position_bottom'] == "bottom") ? $values['widget_position_right'] : "");

      $this->messenger()->addStatus($this->t('Invalid license Key'));
    }
    else {
      $this->messenger()->addStatus($this->t('Your Settings have been saved.'));
    }

    if (!isset($values['statement_link'])) {
      $values['statement_link'] = "";
    }
    if (!isset($values['widget_size'])) {
      $values['widget_size'] = "regularsize";
    }
    if (!isset($values['colorcode'])) {
      $values['colorcode'] = "#420083";
    }
    if (!isset($values['aioa_icon_type'])) {
      $values['aioa_icon_type'] = "aioa-icon-type-1";
    }
    if (!isset($values['aioa_icon_size'])) {
      $values['aioa_icon_size'] = "aioa-medium-icon";
    }
    if (!isset($values['aioa_icon_sizes'])) {
      $values['aioa_icon_sizes'] = "aioa-medium-icon";
    }
    if (!isset($values['is_widget_custom_size'])) {
      $values['is_widget_custom_size'] = '0';
    }
    if (!isset($values['is_widget_custom_size_mobile'])) {
      $values['is_widget_custom_size_mobile'] = '0';
    }
    if (!isset($values['widget_icon_size_custom'])) {
      $values['widget_icon_size_custom'] = '';
    }
    if (!isset($values['widget_icon_size_custom_mobile'])) {
      $values['widget_icon_size_custom_mobile'] = '';
    }
    if (!isset($values['widget_position_left'])) {
      $values['widget_position_left'] = '';
    }
    if (!isset($values['widget_position_bottom'])) {
      $values['widget_position_bottom'] = '';
    }
    if (!isset($values['widget_position_right'])) {
      $values['widget_position_right'] = '';
    }
    if (!isset($values['widget_position_top'])) {
      $values['widget_position_top'] = '';
    }

    $this->configFactory()
      ->getEditable('all_in_one_accessibility.userid.settings')
      ->set('userid', $values['userid'])
      ->save();

    $this->configFactory()
      ->getEditable('all_in_one_accessibility.userid.settings')
      ->set('colorcode', $values['colorcode'])
      ->save();

    $this->configFactory()
      ->getEditable('all_in_one_accessibility.userid.settings')
      ->set('statement_link', $values['statement_link'])
      ->save();

    $this->configFactory()
      ->getEditable('all_in_one_accessibility.userid.settings')
      ->set('position', $values['position'])
      ->save();

    $this->configFactory()
      ->getEditable('all_in_one_accessibility.userid.settings')
      ->set('widget_size', $values['widget_size'])
      ->save();

    $this->configFactory()
      ->getEditable('all_in_one_accessibility.userid.settings')
      ->set('aioa_icon_type', $values['aioa_icon_type'])
      ->save();

    $this->configFactory()
      ->getEditable('all_in_one_accessibility.userid.settings')
      ->set('aioa_icon_size', $values['aioa_icon_size'])
      ->save();

    $this->configFactory()
      ->getEditable('all_in_one_accessibility.userid.settings')
      ->set('aioa_icon_sizes', $values['aioa_icon_sizes'])
      ->save();

    $this->configFactory()
      ->getEditable('all_in_one_accessibility.userid.settings')
      ->set('is_widget_custom_size', $values['is_widget_custom_size'])
      ->save();

    $this->configFactory()
      ->getEditable('all_in_one_accessibility.userid.settings')
      ->set('is_widget_custom_size_mobile', $values['is_widget_custom_size_mobile'])
      ->save();

    $this->configFactory()
      ->getEditable('all_in_one_accessibility.userid.settings')
      ->set('widget_icon_size_custom', $values['widget_icon_size_custom'])
      ->save();

    $this->configFactory()
      ->getEditable('all_in_one_accessibility.userid.settings')
      ->set('widget_icon_size_custom_mobile', $values['widget_icon_size_custom_mobile'])
      ->save();

    $this->configFactory()
      ->getEditable('all_in_one_accessibility.userid.settings')
      ->set('is_widget_custom_position', $values['is_widget_custom_position'])
      ->save();

    $this->configFactory()
      ->getEditable('all_in_one_accessibility.userid.settings')
      ->set('widget_position_left', $values['widget_position_left'])
      ->save();

    $this->configFactory()
      ->getEditable('all_in_one_accessibility.userid.settings')
      ->set('widget_position_bottom', $values['widget_position_bottom'])
      ->save();

    $this->configFactory()
      ->getEditable('all_in_one_accessibility.userid.settings')
      ->set('widget_position_right', $values['widget_position_right'])
      ->save();

    $this->configFactory()
      ->getEditable('all_in_one_accessibility.userid.settings')
      ->set('widget_position_top', $values['widget_position_top'])
      ->save();

    $this->configFactory()
      ->getEditable('all_in_one_accessibility.userid.settings')
      ->set('nofreeversion', $values['nofreeversion'])
      ->save();

    $post_field = [
      'u' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
      'widget_position' => $values['position'],
      'widget_color_code' => $values['colorcode'],
      'statement_link' => (!empty($values['statement_link']) ? $values['statement_link'] : ""),
      'widget_size' => ($values['widget_size'] == 'regularsize' ? 0 : 1),
      'widget_icon_type' => (!empty($values['aioa_icon_type']) ? $values['aioa_icon_type'] : "aioa-icon-type-1"),
      'widget_icon_size' => (!empty($values['aioa_icon_size']) ? $values['aioa_icon_size'] : "aioa-medium-icon"),
      'widget_icon_size_mobile' => (!empty($values['aioa_icon_sizes']) ? $values['aioa_icon_sizes'] : "aioa-medium-icon"),
      'is_widget_custom_size_mobile' => (!empty($values['is_widget_custom_size_mobile']) ? $values['is_widget_custom_size_mobile'] : "0"),
      'is_widget_custom_size' => (!empty($values['is_widget_custom_size']) ? $values['is_widget_custom_size'] : "0"),
      'widget_icon_size_custom' => (!empty($values['widget_icon_size_custom']) ? $values['widget_icon_size_custom'] : ""),
      'widget_icon_size_custom_mobile' => (!empty($values['widget_icon_size_custom_mobile']) ? $values['widget_icon_size_custom_mobile'] : ""),
      'is_widget_custom_position' => (!empty($values['is_widget_custom_position']) ? $values['is_widget_custom_position'] : "0"),
      'widget_position_left' => ((!empty($values['widget_position_top']) && $values['widget_position_top'] == "left") ? $values['widget_position_left'] : ""),
      'widget_position_top' => ((!empty($values['widget_position_bottom']) && $values['widget_position_bottom'] == "top") ? $values['widget_position_right'] : ""),
      'widget_position_right' => ((!empty($values['widget_position_top']) && $values['widget_position_top'] == "right") ? $values['widget_position_left'] : ""),
      'widget_position_bottom' => ((!empty($values['widget_position_bottom']) && $values['widget_position_bottom'] == "bottom") ? $values['widget_position_right'] : ""),

    ];
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => 'https://ada.skynettechnologies.us/api/widget-setting-update-platform',
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => TRUE,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => $post_field,
    ]);
    curl_exec($curl);
    curl_close($curl);
  }

}
