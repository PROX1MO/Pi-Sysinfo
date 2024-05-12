<?php
//	modified by PROXIMO https://github.com/PROX1MO/Pi-Sysinfo
//	compatible with PHP 7.2 and Buster
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Sat, 26 May 1983 13:00:00 GMT");
	header("Pragma: no-cache");

	function NumberWithCommas($in)
	{
		return number_format($in);
	}
	function  WriteToStdOut($text)
	{
		$stdout = fopen('php://stdout','w') or die($php_errormsg);
		fputs($stdout, "\n" . $text);
	}

//	$ip = exec("ifconfig eth0 | grep 'inet addr'| cut -d: -f2 | cut -d' ' -f1");	//local ip
	$ip = exec("curl ifconfig.co");	//external ip
	$current_time = exec("date +'%d %b %Y - %T'");
	$users = exec("who | wc -l");
	$frequency = exec("cat /sys/devices/system/cpu/cpu0/cpufreq/scaling_cur_freq") / 1000;
	$processor = str_replace("-compatible processor", "", explode(": ", exec("cat /proc/cpuinfo | grep Processor"))[1]);
	$cpufrequtil = exec("cpufreq-info -s -m | grep 'MHz' | cut -d'(' -f1 | cut -d, -f1").','.exec("cpufreq-info -s -m | grep 'MHz' | cut -d'(' -f1 | cut -d, -f7") ;		//requires cpufrequtils > sudo apt-get install cpufrequtils
	$distro = exec("lsb_release -a | grep Description | cut -d: -f2");
	$cpu_temperature = round(exec("cat /sys/class/thermal/thermal_zone0/temp ") / 1000, 1);
	$RX = round(exec("ifconfig eth0 | grep 'RX packets' | cut -d's' -f3 | cut -d' ' -f2") /1000000, 2);
	$RXer = exec("ifconfig eth0 | grep 'RX errors' | cut -d's' -f2 | cut -d' ' -f2");
	$RXdr = exec("ifconfig eth0 | grep 'RX errors' | cut -d'd' -f3 | cut -d' ' -f2");
	$TX = round(exec("ifconfig eth0 | grep 'TX packets' | cut -d's' -f3 | cut -d' ' -f2") /1000000, 2);
	$TXer = exec("ifconfig eth0 | grep 'TX errors' | cut -d'd' -f3 | cut -d' ' -f2");
	$TXdr = exec("ifconfig eth0 | grep 'TX errors' | cut -d'd' -f3 | cut -d' ' -f2");
	$proc_all = exec("ps -A | wc -l");
	$proc_sleep = exec("ps -N | wc -l");
	$proc_run = exec("ps r | wc -l");
	$proc_stop = exec("ps s | wc -l");
	$cores = exec("nproc");
//	$loadaverages = exec("uptime | cut -d':' -f5");
	$locale = exec("locale -a");
//	list($system, $host, $kernel) = split(" ", exec("uname -a"), 4);
	list($system, $host, $kernel) = preg_split("/ /", exec("uname -a"), 4);

	//Uptime
	$uptime_array = explode(" ", exec("cat /proc/uptime"));
	$seconds = round($uptime_array[0], 0);
	$minutes = $seconds / 60;
	$hours = $minutes / 60;
	$days = floor($hours / 24);
	$hours = sprintf('%02d', floor($hours - ($days * 24)));
	$minutes = sprintf('%02d', floor($minutes - ($days * 24 * 60) - ($hours * 60)));
	if ($days == 0):
		$uptime = $hours . " h " .  $minutes . " m";
	elseif($days == 1):
		$uptime = $days . " day " .  $hours . " h " .  $minutes . " m";
	else:
		$uptime = $days . " days " .  $hours . " h " .  $minutes . " m";
	endif;

	//CPU Usage
	$output1 = null;
	$output2 = null;
	//First sample
	exec("cat /proc/stat", $output1);
	//Sleep before second sample
	sleep(1);
	//Second sample
	exec("cat /proc/stat", $output2);
	$cpuload = 0;
	for ($i=0; $i < 1; $i++)
	{
		//First row
		$cpu_stat_1 = explode(" ", $output1[$i+1]);
		$cpu_stat_2 = explode(" ", $output2[$i+1]);
		//Init arrays
		$info1 = array("user"=>$cpu_stat_1[1], "nice"=>$cpu_stat_1[2], "system"=>$cpu_stat_1[3], "idle"=>$cpu_stat_1[4]);
		$info2 = array("user"=>$cpu_stat_2[1], "nice"=>$cpu_stat_2[2], "system"=>$cpu_stat_2[3], "idle"=>$cpu_stat_2[4]);
		$idlesum = $info2["idle"] - $info1["idle"] + $info2["system"] - $info1["system"];
		$sum1 = array_sum($info1);
		$sum2 = array_sum($info2);
		//Calculate the cpu usage as a percent
		$load = (1 - ($idlesum / ($sum2 - $sum1))) * 100;
		$cpuload += $load;
	}
	$cpuload = round($cpuload, 1); //One decimal place

	// Load averages
	$loadavg = file("/proc/loadavg");
	if (is_array($loadavg)) {
		$loadaverages = strtok($loadavg[0], " ");
		for ($i = 0; $i < 2; $i++) {
			$retval = strtok(" ");
			if ($retval === FALSE) break; else $loadaverages .= ", " . $retval;
		}
	}

	//Memory Utilisation
	$meminfo = file("/proc/meminfo");
	for ($i = 0; $i < count($meminfo); $i++)
	{
		list($item, $data) = preg_split("/:/", $meminfo[$i], 2);
		$item = trim(chop($item));
		$data = intval(preg_replace("/[^0-9]/", "", trim(chop($data)))); //Remove non numeric characters
		switch($item)
		{
			case "MemTotal": $total_mem = $data; break;
			case "MemAvailable": $free_mem = $data; break;
			case "SwapTotal": $total_swap = $data; break;
			case "SwapFree": $free_swap = $data; break;
			case "Buffers": $buffer_mem = $data; break;
			case "Cached": $cache_mem = $data; break;
			default: break;
		}
	}
	$used_mem = $total_mem - $free_mem;
	$used_swap = $total_swap - $free_swap;
	$percent_free = round(($free_mem / $total_mem) * 100);
	$percent_used = round(($used_mem / $total_mem) * 100);
	$percent_swap = round((($total_swap - $free_swap ) / $total_swap) * 100);
	$percent_swap_free = round(($free_swap / $total_swap) * 100);
	$percent_buff = round(($buffer_mem / $total_mem) * 100);
	$percent_cach = round(($cache_mem / $total_mem) * 100);
	$used_mem = NumberWithCommas($used_mem /1024,0);
	$used_swap = NumberWithCommas($used_swap /1024,0);
	$total_mem = NumberWithCommas($total_mem /1024,0);
	$free_mem = NumberWithCommas($free_mem /1024,0);
	$total_swap = NumberWithCommas($total_swap /1024,0);
	$free_swap = NumberWithCommas($free_swap /1024,0);
	$buffer_mem = NumberWithCommas($buffer_mem /1024,0);
	$cache_mem = NumberWithCommas($cache_mem /1024,0);
	$scale = 1.7; //Bar lenght *X
	
	//Disk space check
	exec("df -T -BM -x tmpfs -x devtmpfs -x rootfs", $diskfree);
	$count = 1;
	while ($count < sizeof($diskfree))
	{
		list($drive[$count], $typex[$count], $size[$count], $used[$count], $avail[$count], $percent[$count], $mount[$count]) = preg_split("/ +/", $diskfree[$count]);
		$percent_part[$count] = str_replace( "%", "", $percent[$count]);
		$count++;
	}
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<title>SYSinfo: <?php echo $host; ?></title>
		<link rel="shortcut icon" href="data:image/x-icon;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyJpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMC1jMDYxIDY0LjE0MDk0OSwgMjAxMC8xMi8wNy0xMDo1NzowMSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNS4xIFdpbmRvd3MiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6NDM4NjA2MjZGMzJEMTFFMDk4QjVEODUxRTI3ODA3QTIiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6NDM4NjA2MjdGMzJEMTFFMDk4QjVEODUxRTI3ODA3QTIiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDo0Mzg2MDYyNEYzMkQxMUUwOThCNUQ4NTFFMjc4MDdBMiIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDo0Mzg2MDYyNUYzMkQxMUUwOThCNUQ4NTFFMjc4MDdBMiIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PtyzKqEAAAJlSURBVDjLdZNLSNRRFMbPvfc45hiT5CuyUcypkIpIGw21NB9JKZhuTJHa9Fi0yJ4LIZSgXPSgRUH0QpCSMKk2BVEI1cKSkLJFRGiEELZoUwkt5Ou7VlLDzP1zmP+5fN/vnnPP/AWA+OCyjBZrzY3AYjuuyeYR84MMZbQ6NfeT0+04fweZ72GkzPn+ASwJRQwa+gQ7bgua7wpKjgqMleHCDu7d4d6goOmmIHeLeENFLCAYzDPTHU9/C+suCFofCbKjNN8T1F4SNPYLdr0QhKvmAJFYgF/dJZ2CnSM00RxuoTAgSK2gmRW10Vx9VmCdGfLieADD50FRMw37FLkBRZMsQtgq6todorsJVBmlLi0RIGeDJE+OSSnqJYSHdh2gDXjrNqJdMvBSolgrgQnqshIBDlx1K3Hc5qDJpqHTLsUxvp92+dhrs9Br83HZrfDi/fEAmZnixgtNEs7bAp68FR+1AmGjeKUlzOsx4Faj3ASRZ/S9r/Y/gBE5+ditR5cNY0o345tW46fWoMykYIKgGeZftQpn3HI802JQ3xsL6Pukm1DJE/pdIX7QcIXt+IqOsI3PhA65NSg1C/BByz3gemwL9dtsCI2MbOMQofEWS/5O0HNXPJd7WI/NRa1JnaW+Jt4lvvmilRjVKFZRPMMWfCW+/3abPn8XGcZNxJ0Cy+rxZSKpESd4Upddhndahosugm7mfqQjhKuYgURjTEsRO+zHtd2EUGNSccrmoY2n15mFOMfpZIt7TV1+IoBfjnGIn+bUEy3CrNZhTEsRFDvN/cP+m/krTASY/1cSco13Mfmn5IJYgff9As2Wgq1FkDRlAAAAAElFTkSuQmCC" />
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    	<meta http-equiv="refresh" content="60" />
		<style type="text/css">
			#Content
			{
				width:460px;
				margin:0px auto;
				text-align:left;
				padding:15px;
				border:1px dashed #333333;
			}
			a
			{
				color:white;
				padding-top:5px;
				display:block;
			}
			a:hover
			{
				text-decoration:none;
			}
			td
			{
				font-family:"DejaVu Sans",Arial,Helvetica,sans-serif;
				font-size:12px;
				vertical-align:top;
				padding-left:2px;
				padding-right:2px;
				background:#1c1c1c;
			}
			td.center
			{
				text-align:center;
			}
			td.head
			{
				font-weight:bold;
				padding-top:3px;
				padding-bottom:3px;
			}
			td.d0
			{
			background-color: #404040;
			}
			td.d1
			{
			background: #404040 url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyJpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMC1jMDYxIDY0LjE0MDk0OSwgMjAxMC8xMi8wNy0xMDo1NzowMSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNS4xIFdpbmRvd3MiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6NDM4NjA2MjZGMzJEMTFFMDk4QjVEODUxRTI3ODA3QTIiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6NDM4NjA2MjdGMzJEMTFFMDk4QjVEODUxRTI3ODA3QTIiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDo0Mzg2MDYyNEYzMkQxMUUwOThCNUQ4NTFFMjc4MDdBMiIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDo0Mzg2MDYyNUYzMkQxMUUwOThCNUQ4NTFFMjc4MDdBMiIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PtyzKqEAAAJlSURBVDjLdZNLSNRRFMbPvfc45hiT5CuyUcypkIpIGw21NB9JKZhuTJHa9Fi0yJ4LIZSgXPSgRUH0QpCSMKk2BVEI1cKSkLJFRGiEELZoUwkt5Ou7VlLDzP1zmP+5fN/vnnPP/AWA+OCyjBZrzY3AYjuuyeYR84MMZbQ6NfeT0+04fweZ72GkzPn+ASwJRQwa+gQ7bgua7wpKjgqMleHCDu7d4d6goOmmIHeLeENFLCAYzDPTHU9/C+suCFofCbKjNN8T1F4SNPYLdr0QhKvmAJFYgF/dJZ2CnSM00RxuoTAgSK2gmRW10Vx9VmCdGfLieADD50FRMw37FLkBRZMsQtgq6todorsJVBmlLi0RIGeDJE+OSSnqJYSHdh2gDXjrNqJdMvBSolgrgQnqshIBDlx1K3Hc5qDJpqHTLsUxvp92+dhrs9Br83HZrfDi/fEAmZnixgtNEs7bAp68FR+1AmGjeKUlzOsx4Faj3ASRZ/S9r/Y/gBE5+ditR5cNY0o345tW46fWoMykYIKgGeZftQpn3HI802JQ3xsL6Pukm1DJE/pdIX7QcIXt+IqOsI3PhA65NSg1C/BByz3gemwL9dtsCI2MbOMQofEWS/5O0HNXPJd7WI/NRa1JnaW+Jt4lvvmilRjVKFZRPMMWfCW+/3abPn8XGcZNxJ0Cy+rxZSKpESd4Upddhndahosugm7mfqQjhKuYgURjTEsRO+zHtd2EUGNSccrmoY2n15mFOMfpZIt7TV1+IoBfjnGIn+bUEy3CrNZhTEsRFDvN/cP+m/krTASY/1cSco13Mfmn5IJYgff9As2Wgq1FkDRlAAAAAElFTkSuQmCC") no-repeat;
			padding-left: 17px; /* Equal to width of new image */
			}
			td.right
			{
				text-align:right;
				padding-right:6px;
			}
			td.left
			{
				text-align:left;
				padding-left:6px;
			}
			table
			{
				width:460px; border-spacing:0;
				border-collapse:collapse;
			}
			html,body,.darkbackground
			{
				background: url("data:image/png;base64,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");
			}
			body
			{
				color:#E6E6E6;
			}
			td.column1
			{
				width:120px;
			}
			td.column3
			{
				width:185px;
			}
			td.column4
			{
				width:20px;
			}
			div#bar1, div#bar2, div#bar3, div#bar4, div#bar5, div#bar6
			{
				height:15px;
				width:0px;
				transition:width 2s;
				<?php
					$agent = "";
					if(isset($_SERVER['HTTP_USER_AGENT']))
					{
						$agent = $_SERVER['HTTP_USER_AGENT'];
					}
					if(strlen(stristr($agent,"applewebkit")) > 0 ) echo "\n\t\t\t\t-webkit-transition:width 2s;\n";
					else if(strlen(stristr($agent,"gecko")) > 0 ) echo "\n\t\t\t\t-moz-transition:width 2s;\n";
					else if(strlen(stristr($agent,"opera")) > 0 ) echo "\n\t\t\t\t-o-transition:width 2s;\n";
				?>
			}
			div#bar1 { background-color:#D78787; }
			div#bar2 { background-color:#AFD787; }
			div#bar3 { background-color:#F7F7AF; }
			div#bar4 { background-color:#87AFD7; }
			div#bar5 { background-color:#D7AFD7; }
			div#bar6 { background-color:#AFD7D7; }
		</style>
		<script type="text/javascript">
			function updateText(objectId, text)
			{
				document.getElementById(objectId).textContent = text;
			}
			function updateHTML(objectId, html)
			{
				document.getElementById(objectId).innerHTML = html;
			}
			function updateDisplay()
			{
<?php
				echo "\n\t\t\t\tupdateText(\"host\",\"$host\" + \" ($ip)\");";
				echo "\n\t\t\t\tupdateText(\"time\",\"$current_time\");";
//				echo "\n\t\t\t\tupdateText(\"users\",\"$users\");";
				echo "\n\t\t\t\tupdateText(\"kernel\",\"$system\" + \" \" + \"$kernel\");";
				echo "\n\t\t\t\tupdateText(\"processor\",\"$processor\");";
				echo "\n\t\t\t\tupdateText(\"distro\",\"$distro\");";
				echo "\n\t\t\t\tupdateText(\"freq\",\"$frequency\" + \" MHz / \" + \"(x $cores) / \" + \"$users\");";
				echo "\n\t\t\t\tupdateText(\"cpuload\",\"$cpuload% / \" + \"$loadaverages\");";
//				echo "\n\t\t\t\tupdateText(\"loadavg\",\"$loadaverages\");";
				echo "\n\t\t\t\tupdateText(\"cpu_temperature\",\"$cpu_temperature\" + \"°C\");";
				echo "\n\t\t\t\tupdateText(\"processes\",\"$proc_all\" + \" ($proc_run run, \" + \"$proc_sleep sleep, \" + \"$proc_stop stop)\");";
				echo "\n\t\t\t\tupdateText(\"local\",\"$locale\");";
				echo "\n\t\t\t\tupdateText(\"cpufrequtil\",\"$cpufrequtil\");";
				echo "\n\t\t\t\tupdateText(\"uptime\",\"$uptime\");";

				echo "\n\t\t\t\tupdateText(\"total_mem\",\"$total_mem\" + \" MB\");";
				echo "\n\t\t\t\tupdateText(\"used_mem\",\"$used_mem\" + \" MB\");";
				echo "\n\t\t\t\tupdateText(\"percent_used\",\"$percent_used%\");";
				echo "\n\t\t\t\tupdateText(\"free_mem\",\"$free_mem\" + \" MB\");";
				echo "\n\t\t\t\tupdateText(\"percent_free\",\"$percent_free%\");";
				echo "\n\t\t\t\tupdateText(\"buffer_mem\",\"$buffer_mem\" + \" MB\");";
				echo "\n\t\t\t\tupdateText(\"percent_buff\",\"$percent_buff%\");";
				echo "\n\t\t\t\tupdateText(\"cache_mem\",\"$cache_mem\" + \" MB\");";
				echo "\n\t\t\t\tupdateText(\"percent_cach\",\"$percent_cach%\");";

				echo "\n\t\t\t\tupdateText(\"rx\",\"$RX\" + \" MB\");";
				echo "\n\t\t\t\tupdateText(\"rxerd\",\"$RXer\" + \" / $RXdr\");";
				echo "\n\t\t\t\tupdateText(\"tx\",\"$TX\" + \" MB\");";
				echo "\n\t\t\t\tupdateText(\"txerd\",\"$TXer\" + \" / $TXdr\");";

				echo "\n\t\t\t\tupdateText(\"total_swap\",\"$total_swap\" + \" MB\");";
				echo "\n\t\t\t\tupdateText(\"used_swap\",\"$used_swap\" + \" MB\");";
				echo "\n\t\t\t\tupdateText(\"percent_swap\",\"$percent_swap%\");";
				echo "\n\t\t\t\tupdateText(\"free_swap\",\"$free_swap\" + \" MB\");";
				echo "\n\t\t\t\tupdateText(\"percent_swap_free\",\"$percent_swap_free%\");\n";
?>
				document.getElementById("bar1").style.width = "<?php echo round($percent_used * $scale); ?>px";
				document.getElementById("bar2").style.width = "<?php echo round($percent_free * $scale); ?>px";
				document.getElementById("bar3").style.width = "<?php echo round($percent_buff * $scale); ?>px";
				document.getElementById("bar4").style.width = "<?php echo round($percent_cach * $scale); ?>px";
				document.getElementById("bar5").style.width = "<?php echo round($percent_swap * $scale); ?>px";
				document.getElementById("bar6").style.width = "<?php echo round($percent_swap_free * $scale); ?>px";
			}
		</script>
	</head>
	<body onload="Javascript: updateDisplay();">
	<div id="Content">
		<table align="center">
			<tr>
				<td colspan="4" class="head center">Raspberry Info</td>
			</tr>
			<tr>
				<td colspan="2" class="d0">Hostname</td>
				<td colspan="2" class="d0" id="host"></td>
			</tr>
			<tr>
				<td colspan="2">System Time</td>
				<td colspan="2" id="time"></td>
			</tr>
			<tr>
				<td colspan="2" class="d0">Kernel</td>
				<td colspan="2" class="d0" id="kernel"></td>
			</tr>
			<tr>
				<td colspan="2">Processor</td>
				<td colspan="2" id="processor"></td>
			</tr>
			<tr>
				<td colspan="2" class="d0">Distribution</td>
				<td colspan="2" class="d1" id="distro"></td>
			</tr>
			<tr>
				<td colspan="2">Freq / Cores / Users</td>
				<td colspan="2" id="freq"></td>
			</tr>
			<tr>
                <td colspan="2" class="d0">CPU Freq Stats</td>
                <td colspan="2" class="d0" id="cpufrequtil"></td>
            </tr>
			<tr>
				<td colspan="2">CPU Load / Averages</td>
				<td colspan="2" id="cpuload"></td>
			</tr>
			<tr>
				<td colspan="2" class="d0">CPU Temperature</td>
				<td colspan="2" class="d0" id="cpu_temperature"></td>
			</tr>
			<tr>
				<td colspan="2">Processes</td>
				<td colspan="2" id="processes"></td>
			</tr>
			<tr>
				<td colspan="2" class="d0">Locale</td>
				<td colspan="2" class="d0" id="local"></td>
			</tr>
			<tr>
				<td colspan="2">Uptime</td>
				<td colspan="2" id="uptime"></td>
			</tr>
			<tr>
				<td colspan="4" class="darkbackground">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="2" class="head right">Memory:</td>
				<td colspan="2" class="head" id="total_mem"></td>
			</tr>
			<tr>
				<td class="column1 d0">Used</td>
				<td class="right d0" id="used_mem"></td>
				<td class="column3 d0"><div id="bar1">&nbsp;</div></td>
				<td class="right column4 d0" id="percent_used"></td>
			</tr>
			<tr>
				<td>Free</td>
				<td class="right" id="free_mem"></td>
				<td><div id="bar2"></div></td>
				<td class="right" id="percent_free"></td>
			</tr>
			<tr>
				<td class="d0">Buffered</td>
				<td class="right d0" id="buffer_mem"></td>
				<td class="d0"><div id="bar3"></div></td>
				<td class="right d0" id="percent_buff"></td>
			</tr>
			<tr>
				<td>Cached</td>
				<td class="right" id="cache_mem"></td>
				<td><div id="bar4"></div></td>
				<td class="right" id="percent_cach"></td>
			</tr>
			<tr>
				<td colspan="4" class="darkbackground">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="2" class="head right">Swap:</td>
				<td colspan="2" class="head" id="total_swap"></td>
			</tr>
			<tr>
				<td class="d0">Used</td>
				<td class="right d0" id="used_swap"></td>
				<td class="d0"><div id="bar5"></div></td>
				<td class="right d0" id="percent_swap"></td>
			</tr>
			<tr>
				<td>Free</td>
				<td class="right" id="free_swap"></td>
				<td><div id="bar6"></div></td>
				<td class="right" id="percent_swap_free"></td>
			</tr>
			<tr>
				<td colspan="4" class="darkbackground">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="4" class="head center">Network Usage</td>
			</tr>
			<tr>
				<td class="d0">Received</td>
				<td class="right d0" id="rx"></td>
				<td class="d0">Errors / Dropped</td>
				<td class="right d0" id="rxerd"></td>
			</tr>
			<tr>
				<td>Sent</td>
				<td class="right" id="tx"></td>
				<td>Errors / Dropped</td>
				<td class="right" id="txerd"></td>
			</tr>
			<tr>
				<td colspan="4" class="darkbackground">&nbsp;</td>
			</tr>
			</table>
		<table id="tblDiskSpace" align="center">
			<tr>
				<td colspan="4" class="head center">Disk Usage</td>
			</tr>
<?php
	for ($i = 1; $i < $count; $i++)
	{
		$total = NumberWithCommas(intval(preg_replace("/[^0-9]/", "", trim($size[$i])))) . " MB";
		$usedspace = NumberWithCommas(intval(preg_replace("/[^0-9]/", "", trim($used[$i])))) . " MB";
		$freespace = NumberWithCommas(intval(preg_replace("/[^0-9]/", "", trim($avail[$i])))) . " MB";
		echo "\n\t\t\t<tr>";
		echo "\n\t\t\t\t<td class=\"head\" colspan=\"4\">" . $mount[$i] . " (" . $typex[$i] . ")</td>";
		echo "\n\t\t\t</tr>";
		echo "\n\t\t\t<tr>";
		echo "\n\t\t\t\t<td class=\"d0\">&nbsp;</td>";
		echo "\n\t\t\t\t<td class=\"d0\">Total Size</td>";
		echo "\n\t\t\t\t<td class=\"right d0\">" . $total . "</td>";
		echo "\n\t\t\t\t<td class=\"right d0\">&nbsp;</td>";
		echo "\n\t\t\t</tr>";
		echo "\n\t\t\t<tr>";
		echo "\n\t\t\t\t<td>&nbsp;</td>";
		echo "\n\t\t\t\t<td>Used</td>";
		echo "\n\t\t\t\t<td class=\"right\">" . $usedspace . "</td>";
		echo "\n\t\t\t\t<td class=\"right\">" . $percent[$i] . "</td>";
		echo "\n\t\t\t</tr>";
		echo "\n\t\t\t<tr>";
		echo "\n\t\t\t\t<td class=\"d0\">&nbsp;</td>";
		echo "\n\t\t\t\t<td class=\"d0\">Available</td>";
		echo "\n\t\t\t\t<td class=\"right d0\">" . $freespace . "</td>";
		echo "\n\t\t\t\t<td class=\"right d0\">" . (100-(floatval($percent_part[$i]))) . "%</td>";
		echo "\n\t\t\t</tr>";
		if ($i < $count-1):
			echo "\n\t\t\t<tr><td colspan=\"4\">&nbsp;</td></tr>";
		endif;
	}
?>
		</table>
		<table align="center">
			<tr>
				<td class="left darkbackground"><a style="font-size:12px; color: #000000;" href="https://github.com/PROX1MO/Pi-Sysinfo" title="Download" target="_blank">Source</a></td><td class="right darkbackground"><a style="font-size:12px; color: #000000;" href="javascript:location.reload(true);" title="Auto refresh every 60s">Refresh</a></td>
			</tr>
		</table>
	</div>
	</body>
</html>
