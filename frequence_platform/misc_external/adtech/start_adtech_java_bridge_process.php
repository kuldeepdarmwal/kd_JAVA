<?php

// usage: php <command> <java-path> <adtech-directory>
// usage: php start_adtech_java_bridge_process.php /usr/lib/jvm/java-6-openjdk/bin /var/review_servers/review-3/public/bin/adtech


$java_path="/usr/lib/jvm/java-6-openjdk/bin";
$base_dir="/var/review_servers/review-3/public/bin/adtech";

if(isset($argc) && $argc == 3)
{
	$java_path=rtrim($argv[1], '/\\');
	$base_dir=rtrim($argv[2], '/\\');
}

function get_running_processes()
{
	$get_running_adtech_java_bridge_processes_command = "ps aux | grep '[A]dtechJavaBridge'";
	$processes = shell_exec($get_running_adtech_java_bridge_processes_command);

	$process_lines = preg_split('/[\v]+/', $processes);
	$process_ids = '';
	foreach($process_lines as $index => $process_line)
	{
		if(!empty($process_line))
		{
			$process_elements = preg_split('/[\s]+/', $process_line);
			if(count($process_elements) >= 2)
			{
				if(empty($process_ids))
				{
					$process_ids .= $process_elements[1];
				}
				else
				{
					$process_ids .= ', '.$process_elements[1];
				}
			}
		}
	}
	
	return $process_ids;
}

$start_java_bridge_command = "nohup $java_path/java -Djava.security.auth.login.config=$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/conf/jaas.config -Dfile.encoding=UTF-8 -classpath $base_dir/eclipse_project/bin:$base_dir/phpjava/JavaBridge.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/lib/security-ng.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/lib/security2-ng.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/lib/xercesImpl.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/lib/xmlParserAPIs.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/lib/jetty.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/lib/wasp.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/lib/builtin_serialization.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/lib/core_services_client.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/lib/activation.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/lib/jnet.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/lib/US_export_policy.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/lib/saaj.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/lib/jaxm.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/lib/jaxrpc.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/lib/wsdl_api.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/lib/db_interface.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/lib/transactions.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/lib/persistent_store.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/lib/runner.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/lib/security_providers_client.jar:$base_dir/phpjava/HeliosWSClientSystem_1.16.1a/HeliosWSClientSystem.jar:$base_dir/google-gson-2.2.4/gson-2.2.4.jar adtech_java_bridge.AdtechJavaBridge";
$return_java_bridge_exec_immediately = " > adtech_java_bridge.log /dev/null 2> adtech_java_bridge_errors.log &";

$process_ids = get_running_processes();
if(empty($process_ids))
{
	if(file_exists("$java_path/java") &&
		file_exists($base_dir))
	{
		exec($start_java_bridge_command.$return_java_bridge_exec_immediately);

		$new_process_ids = get_running_processes();
		if(empty($new_process_ids))
		{
			echo 'Failed to start AdTech JavaBridge'."\n";
		}
		else
		{
			echo 'AdTech JavaBridge has started'."\n";
		}
	}
	else
	{
		echo 'Failed to start AdTech JavaBridge';
		if(!file_exists("$java_path/java"))
		{
			echo ', java executable not found in"'.$java_path.'"';
		}
		if(!file_exists($base_dir))
		{
			echo ', adtech directory not found in"'.$base_dir.'"';
		}
		echo "\n";
	}
}
else
{
	$process_ids = get_running_processes();
	echo 'AdTech JavaBridge is already running. Process ids: '.$process_ids."\n";
}
?>
