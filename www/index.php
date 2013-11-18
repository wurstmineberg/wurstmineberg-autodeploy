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
  // request must not have any get parameters
  if (count($_GET) > 0) no_way();

  // request must have only one post parameter
  if (count($_POST) == 0 || count($_POST) > 1) no_way();

  // post parameter must be named payload
  if (count($_POST) == 1 && !isset($_POST['payload'])) no_way();

  // check payload array structure
  $payload = json_decode($_POST['payload'], true);

  handle_payload($payload);
}

function no_way()
{
  header('HTTP/1.1 301 Moved Permanently');
  header('Location: http://wurstmineberg.de/');
  exit();
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
