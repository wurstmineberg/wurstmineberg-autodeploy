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

$gearman = null;
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
  chdir(dirname(__FILE__));
  $config = json_decode(file_get_contents('../config.json');

  $gearman = new GearmanClient();
  $gearman->addServer('127.0.0.1', '4730');

  $rep = $payload['repository'];

  if (in_array($rep['name'], $config['repositories']  // repository is registered for auto deploy...
  &&  in_array($config['owners'], $rep['owner']['name'])) // ...and owner is valid
  {
    $gearman->addTaskBackground(sprintf('deploy.%s', trim($rep['name']), 'no_workload');
  } else no_way();

  $gearman->runTasks();
}
