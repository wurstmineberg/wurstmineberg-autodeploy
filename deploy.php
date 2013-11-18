#!/usr/bin/env php
<?php
chdir(dirname(__FILE__));

$worker = new GearmanWorker();
$worker->addServer('127.0.0.1', '4730');
$worker->addFunction('deploy', function($job)
{
  $config = json_decode(file_get_contents('../config.json'));

  $wl = $job->workload();

  $reponame = $wl['repositoryName'];
  $user = $wl['notifier'];

  // clone/update to <clonedir>/reponame

  $repodir = sprintf("%s/%s", $config['cloneLocation'], $reponame);

  if (!is_dir($repodir))
  {
    // clone and setup all remotes
    exec("git clone {$reponame} {$repodir}");

    $remotes = array_filter($config['whitelist'], function ($el) use ($reponame)
    {
      return @strcmp($el, '*') == 0 || in_array($reponame, $el);
    });

    foreach ($remotes as $remote)
      exec("git remote add {$remote} https://github.com/{$remote}/{$reponame}.git");
  } else
  {
    // update remotes
    chdir($repodir);
    exec("git remote update");
    exec("git branch -r --no-merge", $branches);

    $currentBranch = exec("git rev-parse --abbrev-ref HEAD");

    foreach ($branches as $branch)
    {
      // output is of format remotes/<branch_user>/<branch_name>, first path segment is obv. trash
      list($trash, $branchUser, $banchName) = explode($branch, '/');

      exec("git checkout {$branchname}");
      exec("git rebase remotes/{$user}/{$branch}");
    }

    exec("git checkout {$currentBranch}");

    // check for branchcopy requests
    $branchCopies = $config['branchCopies'][$reponame];
    foreach ($branchCopies as $copy)
    {
      $copyDir = sprintf("%s/branches/%s/%s", $config['cloneLocation'], $reponame, $copy);
      exec("mkdir -p {$copyDir}");

      exec("git checkout {$copy}");
      exec("rsync -az --exclude=.git . {$copy}/");

      chdir($copyDir);

      // execute .wurstmineberg-autodeploy-hook if such a file exists
      // this can be used to trigger build systems in repositories or relaunch depending services
      if (file_exists('.wurstmineberg-autodeploy-hook'))
        exec('./.wurstmineberg-autodeploy-hook');

      chdir($repoDir);
    }
  }
});

for (;;)
{
    $worker->work();
    if ($worker->returnCode() != GEARMAN_SUCCESS) break;
}
