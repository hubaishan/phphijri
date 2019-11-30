<?php

/**
 *
 * This file is part of phpHijri
 *
 * @copyright (c) Saeed Hubaishan <http://www.salafitech.net>
 * @license GNU Lesser General Public License, version 2.1 (LGPL-2.1)
 *
 */
namespace phpHijri;


/**
 * Hijri CalendarAdjustment Class is group of functions that help to get and correct adjustments to Umm Al-Qura Calendar
 *
 */
class CalendarAdjustment extends Calendar
{
	
	/**
	 *
	 * @var array Original Umm Al-Qura Data without any adjustments
	 */
	protected $umdata_clear;
	
	/**
	 *
	 * @var string The date format to get Gregorian dates form adjustment information
	 */
	private $grdate_format;

	/**
	 * Create new hijri\calendarAdjustment object according to given settings
	 *
	 * @global array $hijri_settings global hijri calendar settings
	 * @param array $settings
	 *        	Array contains one or more settings of the hijri
	 *        	calendarAdjustment object these settings are:
	 *        	
	 *        	adj_data: string|array string: contains Um Al-Qura adjustment data that got by function get_adjdata(TRUE) of calendarAdjustment class
	 *        	or array contains Um Al-Qura adjustment data that got by function get_adjdata(FALSE)
	 *        	grdate_format: string date format to show the Gregorian dates in adjustment process
	 *        	if not set, the defaults from $hijri_settings global variable will be used.
	 *        	
	 * @return Calendar phpHijri\CalendarAdjustment object with the specified settings.
	 */
	 public function __construct($settings = array())
	{
		global $hijri_settings;
		parent::__construct($settings);
		if (!empty($this->adj_data))
		{
			$this->umdata = array();
			$this->umdata_clear = $this->get_umalquradata(FALSE, TRUE);
			$this->get_umalquradata();
		}
		else
		{
			$this->get_umalquradata();
			$this->umdata_clear = $this->umdata;
		}
		if (isset($settings['grdate_format']))
		{
			$this->grdate_format = $settings['grdate_format'];
		}
		elseif (isset($settings['grdate_format']))
		{
			$this->grdate_format = $hijri_settings['grdate_format'];
		}
	}

	/**
	 *
	 * @internal
	 *
	 */
	protected function offestToMonth($off)
	{
		$ii = floor(($off) / 12);
		$hy = static::umstartyear + $ii;
		$hm = $off + 1 - 12 * $ii;
		return array(
			$hm, 
			$hy
		);
	}

	/**
	 * returns the adjustment data
	 *
	 * @param bool $txt
	 *        	true to return a JSON array string False to return array.
	 * @return string|array string JSON array adjustment data if $txt is true, or array of adjustment data if $txt if false
	 */
	public function get_adjdata($txt = TRUE)
	{
		asort($this->adj_data);
		if ($txt)
		{
			return json_encode($this->adj_data);
		}
		else
		{
			return $this->adj_data;
		}
	}

	/**
	 * Returns array of month adjustment must be deleted if the given month adjustment deleted
	 *
	 * @param int $off
	 *        	the index of the month adjustment you want to delete can be given by function month2off
	 *        	
	 * @return int[] Array of index of month adjustment must be deleted
	 */
	public function checkAutoDel($off)
	{
		$my_adj = $this->adj_data;
		unset($my_adj[$off]);
		$um_data_adj = array_replace($this->umdata_clear, $my_adj);
		$myret = array();
		for ($noff = $off + 1; array_key_exists($noff, $my_adj); $noff++)
		{
			$mlen = ($um_data_adj[$noff] - $um_data_adj[$noff - 1]);
			if (($mlen < 29) || ($mlen > 30))
			{
				$myret[] = $noff;
				unset($my_adj[$noff]);
				$um_data_adj = array_replace($this->umdata_clear, $my_adj);
			}
			else
			{
				break;
			}
		}
		for ($noff = $off - 1; array_key_exists($noff, $my_adj); $noff--)
		{
			$mlen = $um_data_adj[$noff + 1] - $um_data_adj[$noff];
			if (($mlen < 29) || ($mlen > 30))
			{
				$myret[] = $noff;
				unset($my_adj[$noff]);
				$um_data_adj = array_replace($this->umdata_clear, $my_adj);
			}
			else
			{
				break;
			}
		}
		return $myret;
	}

	/**
	 * Gives you array of adj_data must added if you adjust the given month with the given modified julian day
	 *
	 * @param int $off
	 *        	The index of the month to be adjusted can be given by function month2off
	 * @param int $v
	 *        	(modified julian day) of the new start of the month
	 * @return array Array of adj_data must by applied if the start of month adjusted to the given day
	 */
	public function check_auto_adj($off, $v)
	{
		$my_adj = $this->adj_data;
		$my_adj[$off] = $v;
		$um_data_adj = array_replace($this->umdata_clear, $my_adj);
		$myret = array();
		for ($noff = $off + 1; array_key_exists($noff, $this->umdata); $noff++)
		{
			if (($um_data_adj[$noff] - $um_data_adj[$noff - 1]) < 29)
			{
				
				$myret[$noff] = $um_data_adj[$noff - 1] + 29;
				$um_data_adj = array_replace($this->umdata_clear, $my_adj, $myret);
			}
			elseif (($um_data_adj[$noff] - $um_data_adj[$noff - 1]) > 30)
			{
				$myret[$noff] = $um_data_adj[$noff - 1] + 30;
				$um_data_adj = array_replace($this->umdata_clear, $my_adj, $myret);
			}
			else
			{
				break;
			}
		}
		return $myret;
	}

	/**
	 * Gets an array of possible starts of Hijri month according to Umm Al-Qura Data and current Adjustments
	 *
	 * @param integer $month
	 *        	Hijri month
	 * @param integer $year
	 *        	Hijri year
	 *        	
	 * @return array An array contains two numeric indexed variables (arrays) with these keys:
	 *         
	 *         grdate: stringgregorion date (format d/m/yyyy) of the possible start of the hijri month
	 *         jd: 			int		modifed julian day of the possible start of the hijri month
	 *         currentset: 	bool	determine whether this date is the default start of the Hijri month
	 *         alsoadjdata: array	array contians another adjustments must be applied if the hijri month starts by this date with these keys:
	 *         month:	int		The Hijri month which will be adjusted
	 *         year: 	int		The year of the hijri month which will be adjusted
	 *         grdate:	string	The new start that Hijri month must be started in gregorion date (format d/m/yyyy)
	 *         jd: 		int		The modified julian day of the new start that Hijri month must be started 
	 *         if the given month is out of umalqura range the function will return empty array
	 *        
	 */
	public function getPossibleStarts($month, $year)
	{
		$myret = array();
		$off = $this->month2off($month, $year);
		if ($off > 0 && array_key_exists($off, $this->umdata))
		{
			for ($un = $this->umdata[$off - 1] + 31, $n = $un - 2; $n < $un; $n++)
			{
				$auto_adj = array();
				foreach ($this->check_auto_adj($off, $n) as $k => $v)
				{
					list($hm, $hy) = $this->off2month($k);
					$auto_adj[] = array(
						'month' => $hm, 
						'year' => $hy, 
						'grdate' => $this->myjd2gre($v), 
						'jd' => $v
					);
				}
				$myret[] = array(
					'grdate' => $this->myjd2gre($n), 
					'jd' => $n, 
					'currentset' => ($n == $this->umdata[$off]), 
					'alsoadjdata' => $auto_adj
				);
			}
		}
		return $myret;
	}

	/**
	 * Gives you an array of current Umm Al-Qura adjustments
	 *
	 * @return array An array contains (arrays) of current adjustments with these keys:
	 *         
	 *         month: 	int		The hijri month
	 *         year: 	int		The hijri year of the month
	 *         current: string	Current start of the Hijri month in Gregorian date, the format will be same of grdate_format option or (d/m/yyyy)
	 *         default: string	The original start the Hijri month in Gregorian, the format will be same of grdate_format option or (d/m/yyyy)
	 *        
	 */
	public function get_current_adjs()
	{
		$myret = array();
		foreach ($this->adj_data as $k => $v)
		{
			list($hm, $hy) = $this->off2month($k);
			$myret[] = array(
				'month' => $hm, 
				'year' => $hy, 
				'current' => $this->myjd2gre($v), 
				'default' => $this->myjd2gre($this->umdata_clear[$k])
			);
		}
		return $myret;
	}

	/**
	 * Adds or modifies adjustment to the calendar object
	 *
	 * @param integer $month
	 *        	the Hijri month
	 * @param integer $year
	 *        	the year of Hijri month
	 * @param int|string $new_month_start
	 *        	the new start of the Hijri Month can be integer (modified julian day) or string (Gregorian date (d/m/yyyy) format)
	 * @return boolean TRUE if the succeeded or FALSE if not
	 *        
	 *         this will add the adjustment only to the current object if you want to save it you must use get_adj_txt()
	 */
	public function add_adj($month, $year, $new_month_start)
	{
		$off = $this->month2off($month, $year);
		if (is_numeric($new_month_start))
		{
			$value = $new_month_start;
		}
		else
		{
			list($gd, $gm, $gy) = preg_split('/[-\/.\\\ ]/', $new_month_start);
			$value = gregoriantojd($gm, $gd, $gy) - static::mjd_factor;
		}
		$len = $value - $this->umdata[$off - 1];
		if ($len > 28 && $len < 31)
		{
			$new_adj_data = array_replace(array(
				$off => $value
			), $this->check_auto_adj($off, $value));
			foreach ($new_adj_data as $k => $v)
			{
				if ($this->umdata_clear[$k] == $v)
				{
					unset($this->adj_data[$k]);
				}
				else
				{
					$this->adj_data[$k] = $v;
				}
			}
			asort($this->adj_data, SORT_NUMERIC);
			$this->umdata = array_replace($this->umdata_clear, $this->adj_data);
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Deletes the adjustment of the specified month
	 *
	 * @param integer $month
	 *        	the Hijri month
	 * @param integer $year
	 *        	the Hijri Year
	 * @return void
	 */
	public function del_adj($month, $year)
	{
		$off = $this->month2off($month, $year);
		unset($this->adj_data[$off]);
		$auto_del = $this->check_auto_del($off);
		foreach ($auto_del as $k)
		{
			unset($this->adj_data[$k]);
		}
		$this->umdata = array_replace($this->umdata_clear, $this->adj_data);
	}

	/**
	 * Gives information of must delete adjustments if given month adjustment deleted
	 *
	 * @param integer $month
	 *        	the Hijri month
	 * @param integer $year
	 *        	the Hijri Year
	 * @return array array of must to delete month adjustments, keys are 'month','year'
	 */
	public function auto_del_info($month, $year)
	{
		$myret = array();
		$auto_del = $this->check_auto_del($this->month2off($month, $year));
		
		foreach ($auto_del as $k)
		{
			list($hm, $hy) = $this->off2month($k);
			$myret[] = array(
				'month' => $hm, 
				'year' => $hy
			);
		}
		return $myret;
	}

	/**
	 *
	 * @internal
	 *
	 */
	private function myjd2gre($jd)
	{
		$jddate = jdtogregorian(static::mjd_factor + $jd);
		
		if (isset($this->grdate_format))
		{
			$dt = new \datetime($jddate);
			return $dt->format($this->grdate_format);
		}
		else
		{
			list($gm, $gd, $gy) = explode('/', $jddate);
			return "$gd-$gm-$gy";
		}
	}
}
