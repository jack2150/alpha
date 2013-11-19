-- phpMyAdmin SQL Dump
-- version 3.5.8.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Nov 17, 2013 at 03:37 AM
-- Server version: 5.6.12-log
-- PHP Version: 5.4.14

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: 'system'
--

-- --------------------------------------------------------

--
-- Table structure for table 'holiday'
--

CREATE TABLE IF NOT EXISTS holiday (
  id int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `name` varchar(200) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table 'symbol'
--

CREATE TABLE IF NOT EXISTS symbol (
  id int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  importDate date NOT NULL,
  firstDate date DEFAULT NULL,
  lastDate date DEFAULT NULL,
  `type` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  industry varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  sector varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  country varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  marketCap varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  shortable tinyint(1) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
