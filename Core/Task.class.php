<?php
/**
 * 定时任务
 * @author Yurun <admin@yurunsoft.com>
 */
class Task
{
	/**
	 * 事件绑定记录
	 */
	public static $tasks;
	/**
	 * 加锁文件名
	 */
	public static $lockFileName;
	/**
	 * 是否执行过
	 */
	public static $isExeced = false;
	/**
	 * 按秒执行
	 */
	const MODE_SECOND = 1;
	/**
	 * 按每年执行
	 */
	const MODE_EVERY_YEAR = 2;
	/**
	 * 按每月执行
	 */
	const MODE_EVERY_MONTH = 3;
	/**
	 * 按每周执行
	 */
	const MODE_EVERY_WEEK = 4;
	/**
	 * 按每日执行
	 */
	const MODE_EVERY_DAY = 5;
	/**
	 * 按每小时执行
	 */
	const MODE_EVERY_HOUR = 6;
	/**
	 * 初始化
	 */
	public static function init()
	{
		Config::create('Task', 'php', APP_CONFIG . 'task.php');
		// 获取插件列表
		self::$tasks = Config::get('Task.List',array());
		define('APP_TASK', APP_PATH . 'Task/');
		self::$lockFileName = APP_CACHE . 'task.lock';
		// 判断是否每次请求都会触发定时任务检测
		if(Config::get('Task.Status') && Config::get('Task.ExecPercent',1) >= mt_rand(1,100) / 100)
		{
			register_shutdown_function(function(){
				Task::exec();
			});
		}
		return true;
	}
	/**
	 * 开始执行
	 */
	public static function exec()
	{
		if(self::$isExeced || !self::canExec())
		{
			return false;
		}
		foreach(self::$tasks as $name => $task)
		{
			if(self::taskCanExec($task))
			{
				self::execTask($name,$task);
				break;
			}
		}
		self::$isExeced = true;
		return true;
	}
	/**
	 * 检测当前是否有其它任务在执行，是否可以执行计划任务
	 */
	public static function canExec()
	{
		return !file_exists(self::$lockFileName) || is_writeable(self::$lockFileName);
	}
	public static function taskCanExec($params)
	{
		$result = false;
		foreach($params['WorkTime'] as $param)
		{
			$result = self::checkCanExec($params,$param);
			if($result)
			{
				break;
			}
		}
		return $result;
	}
	/**
	 * 任务是否可执行
	 * @param type $param
	 * @return boolean
	 */
	public static function checkCanExec($params,$param)
	{
		$year = $month = $week = $day = $hour = $minute = $second = $seconds = 0;
		$lastRunTime = (int)$params['LastRunTime'];
		$startTime = (int)$param['StartTime'];
		$stopTime = (int)$param['StopTime'];
		// 还未到开始时间
		if($_SERVER['REQUEST_TIME'] < $startTime)
		{
			return false;
		}
		// 超过结束时间
		if($stopTime > 0 && $_SERVER['REQUEST_TIME'] > $stopTime)
		{
			return false;
		}
		switch((int)$param['Mode'])
		{
			case self::MODE_SECOND:
				$seconds = max((int)$param['Seconds'],1);
				$nextTime = $lastRunTime + $seconds;
				break;
			case self::MODE_EVERY_YEAR:
				$month = max((int)$param['Month'],1);
				$day = max((int)$param['Day'],1);
				$hour = max((int)$param['Hour'],0);
				$minute = max((int)$param['Minute'],0);
				$second = max((int)$param['Second'],0);
				
				$year = date('Y',$lastRunTime) + 1;
				break;
			case self::MODE_EVERY_MONTH:
				$day = max((int)$param['Day'],1);
				$hour = max((int)$param['Hour'],0);
				$minute = max((int)$param['Minute'],0);
				$second = max((int)$param['Second'],0);
				
				$nextMonth = strtotime('+1 month',$lastRunTime);
				$year = date('Y', $nextMonth);
				$month = date('m', $nextMonth);
				break;
			case self::MODE_EVERY_WEEK:
				// 周一-周日=1-7
				$hour = max((int)$param['Hour'],0);
				$minute = max((int)$param['Minute'],0);
				$second = max((int)$param['Second'],0);
				$weeks = is_array($param['Week']) ? $param['Week'] : array($param['Week']);
				sort($weeks);
				$lastTimeWeek = date('N',$lastRunTime);
				foreach($weeks as $week)
				{
					if($lastTimeWeek > $week)
					{
						$days = 7 - ($lastTimeWeek - $week);
					}
					else
					{
						$days = abs($week - $lastTimeWeek);
					}
					$lastNextTime = strtotime('+' . $days . 'days',$lastRunTime);
					$year = date('Y',$lastNextTime);
					$month = date('m',$lastNextTime);
					$day = date('d',$lastNextTime);
					$nextTime = strtotime("{$year}-{$month}-{$day} {$hour}:{$minute}:{$second}");
					if($nextTime > $lastRunTime)
					{
						break;
					}
				}
				if($nextTime <= $lastRunTime)
				{
					return false;
				}
				break;
			case self::MODE_EVERY_DAY:
				$hour = max((int)$param['Hour'],0);
				$minute = max((int)$param['Minute'],0);
				$second = max((int)$param['Second'],0);
				
				$nextDay = strtotime('+1 days',$lastRunTime);
				$year = date('Y', $nextDay);
				$month = date('m', $nextDay);
				$day = date('d',$nextDay);
				break;
			case self::MODE_EVERY_HOUR:
				$minute = max((int)$param['Minute'],0);
				$second = max((int)$param['Second'],0);
				
				$nextHour = strtotime('+1 hour',$lastRunTime);
				$year = date('Y', $nextHour);
				$month = date('m', $nextHour);
				$day = date('d',$nextHour);
				$hour = date('H',$nextHour);
				break;
			default:
				return false;
		}
		if(!isset($nextTime))
		{
			$nextTime = strtotime("{$year}-{$month}-{$day} {$hour}:{$minute}:{$second}");
		}
		return $_SERVER['REQUEST_TIME'] >= $nextTime;
	}
	/**
	 * 执行任务
	 * @param type $name
	 * @param type $task
	 * @return type
	 */
	private static function execTask($name,$task)
	{
		$file = fopen(self::$lockFileName, 'w+');
		if(false === $file)
		{
			return;
		}
		flock($file,LOCK_EX);
		// 执行任务
		$className = $name . 'Task';
		require_once APP_TASK . $className . '.class.php';
		$className::exec();
		Config::set('Task.List.' . $name . '.LastRunTime',time());
		Config::save('Task');
		fclose($file);
		unlink(self::$lockFileName);
	}
}