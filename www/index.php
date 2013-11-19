<?php
/**
 * Handle GitHub Post-Receive update messages
 *
 * GitHub messages are in the first instance identifyable via
 * containing only one post parameter which has to be named
 * `payload` which must at least have the following structure:
 *
 * <code>
 *   {
 *     "after":"",
 *     "before":"",
 *     "commits":[],
 *     "compare":"",
 *     "created":boolean,
 *     "deleted":boolean,
 *     "forced":boolean,
 *     "head_commit":{},
 *     "pusher":{},
 *     "ref":"",
 *     "repository":{}
 *   }
 * </code>
 *
 * @author Stefan Graupner <stefan.graupner@gmail.com>
 **/

chdir(dirname(__FILE__));
handle_request();

function handle_request()
{
  parse_str(file_get_contents('php://input'));

  $payload = json_decode($payload, true);

  store_payload($payload);
  handle_payload($payload);
}

function no_way()
{
  header('HTTP/1.1 301 Moved Permanently');
  header('Location: http://wurstmineberg.de/');
  exit();
}

function store_payload($payload)
{
  if (file_exists('statistics.json') && is_writable('statistics.json'))
  {
    $c = file_get_contents('statistics.json');
    $statistics = (strlen($c) == 0) ? array() : json_decode($c, true);

    if (count($statistics) == 50) array_shift($statistics);
    $statistics[] = $payload;


    file_put_contents('statistics.json', json_encode($statistics));
  }
}

function handle_payload($payload)
{
  $config = json_decode(file_get_contents('../config.json'), true);

  $repositoryName = $payload['repository']['name'];
  $owner          = $payload['repository']['owner']['name'];

  if (in_array($owner, $config['whitelist'])
  && ((@strcmp($config['whitelist'][$owner], '*') == 0)
  || in_array($repositoryName, $config['whitelist'][$owner])))
  {
    $gearman = new GearmanClient();
    $gearman->addServer('127.0.0.1', '4730');

    $gearman->addTaskBackground('deploy',
      array(
        'repositoryName' => $repositoryName,
        'notifier' => $owner
      )
    );

    $gearman->runTasks();
  } else no_way();
}
