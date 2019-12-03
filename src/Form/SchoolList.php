<?php
namespace Drupal\custom_page\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

class SchoolList extends FormBase {

    /**
     * @var \Drupal\Core\Database\Connection;
     */
    protected $database;


    public function __construct(Connection $connection)
    {
        $this->database = $connection;
    }


    public static function create(ContainerInterface $container) {
        return new static(
          $container->get('database')
        );
    }

    public function getFormId()
    {
     return 'school_list';
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        drupal_set_message('Schools List for title:'.$form_state->getValue('title'));
        $form_state->setStorage(['title' => $form_state->getValue('title')]);
        // Show the form again.
        $form_state->setRebuild(TRUE);
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $header = [
            ['data' => 'id'],
            ['data' => 'Title'],
        ];
        //create a school content type
        $query = $this->database->select('node_field_data','n');
        $query->condition('type','page')//change type to school
            ->fields('n',['nid','title']);
        $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);
        $result = $pager->execute()->fetchAll();
        $rows = [];
        if ($form_state->getValue('title')) {
            $temp_rows = array();

            foreach ($rows as $row) {
                if (preg_match("/" . $form_state->getValue('title') . "/", $row['title'])) {
                    $rows[] = [(array) $row];
                }
            }
        }
        else {
            foreach ($result as $row) {
                $rows[] = [(array) $row];
            }
        }

        $form['form']['filters'] = [
            '#type'  => 'fieldset',
            '#title' => $this->t('Filter'),
            '#open'  => true,
        ];

        $form['form']['filters']['title'] = [
            '#title'         => 'Title',
            '#type'          => 'search'
        ];
        $form['form']['filters']['actions'] = [
            '#type'       => 'actions'
        ];

        $form['form']['filters']['actions']['submit'] = [
            '#type'  => 'submit',
            '#value' => $this->t('Filter'),
            '#ajax'  => [
               'callback' => [$this, 'submitForm'],
                'wrapper'  => 'school-table',
                'progress' => [
                  'type' => 'throbber',
                  'message' => $this->t('Refreshing results....'),
                ],
            ]
        ];

        $form['table'] = [
            '#type' => 'tableselect',
            '#header' => $header,
            '#options' => $rows,
            '#empty' => t('No Schools found'),
            '#attributes' => ['id' => 'school-table'],
        ];
        $form['pager'] = [
          '#type' => 'pager',
        ];
        return $form;

    }
}
