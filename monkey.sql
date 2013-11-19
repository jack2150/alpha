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
-- Database: 'monkey'
--

-- --------------------------------------------------------

--
-- Table structure for table 'analyst'
--

CREATE TABLE IF NOT EXISTS analyst (
  id int(11) NOT NULL AUTO_INCREMENT,
  firm varchar(200) DEFAULT NULL,
  opinion tinyint(1) NOT NULL DEFAULT '0' COMMENT 'upgrade 1, downgrade -1, initial 0',
  rating tinyint(1) NOT NULL DEFAULT '3' COMMENT '0 (s-sell), 1 (sell), 2 (hold), 3 (buy), 4 (s-buy)',
  target double NOT NULL,
  eventId int(11) NOT NULL,
  PRIMARY KEY (id),
  KEY eventId (eventId)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table 'chain'
--

CREATE TABLE IF NOT EXISTS `chain` (
  id int(11) NOT NULL AUTO_INCREMENT,
  underlyingId int(11) DEFAULT NULL,
  cycleId int(11) DEFAULT NULL,
  strikeId int(11) DEFAULT NULL,
  dte int(11) NOT NULL,
  bid double NOT NULL,
  ask double NOT NULL,
  delta double NOT NULL,
  gamma double NOT NULL,
  theta double NOT NULL,
  vega double NOT NULL,
  rho double NOT NULL,
  theo double NOT NULL,
  impl double NOT NULL,
  probITM double NOT NULL,
  probOTM double NOT NULL,
  probTouch double NOT NULL,
  volume int(11) NOT NULL,
  openInterest int(11) NOT NULL,
  intrinsic double NOT NULL,
  extrinsic double NOT NULL,
  PRIMARY KEY (id),
  KEY STIRKE (strikeId),
  KEY CYCLE (cycleId),
  KEY UNDERLYING (underlyingId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'cycle'
--

CREATE TABLE IF NOT EXISTS cycle (
  id int(11) NOT NULL AUTO_INCREMENT,
  expireMonth varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  expireYear int(11) NOT NULL,
  expireDate date NOT NULL,
  contractRight int(11) NOT NULL,
  isWeekly tinyint(1) NOT NULL,
  isMini tinyint(1) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'earning'
--

CREATE TABLE IF NOT EXISTS earning (
  id int(11) NOT NULL AUTO_INCREMENT,
  marketHour varchar(7) NOT NULL COMMENT 'before,during,after',
  periodEnding date NOT NULL COMMENT 'season date, sep 2013',
  estimate double NOT NULL,
  actual double NOT NULL,
  eventId int(11) NOT NULL,
  PRIMARY KEY (id),
  KEY eventId (eventId)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table 'event'
--

CREATE TABLE IF NOT EXISTS `event` (
  id int(11) NOT NULL AUTO_INCREMENT,
  underlyingId int(11) DEFAULT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `context` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY underlyingId (underlyingId,`name`),
  KEY `Unique` (underlyingId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'hv'
--

CREATE TABLE IF NOT EXISTS hv (
  id int(11) NOT NULL AUTO_INCREMENT,
  sample int(11) NOT NULL DEFAULT '20',
  `value` int(11) NOT NULL,
  52weekHigh double DEFAULT '0',
  52weekLow double DEFAULT '0',
  rank double DEFAULT '0',
  underlyingId int(11) NOT NULL,
  PRIMARY KEY (id),
  KEY underlyingId (underlyingId)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table 'strike'
--

CREATE TABLE IF NOT EXISTS strike (
  id int(11) NOT NULL AUTO_INCREMENT,
  category varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  strike double NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table 'underlying'
--

CREATE TABLE IF NOT EXISTS underlying (
  id int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `last` double NOT NULL,
  netChange double NOT NULL,
  volume int(11) NOT NULL,
  `open` double NOT NULL,
  high double NOT NULL,
  low double NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `analyst`
--
ALTER TABLE `analyst`
  ADD CONSTRAINT analyst_ibfk_2 FOREIGN KEY (eventId) REFERENCES event (id);

--
-- Constraints for table `chain`
--
ALTER TABLE `chain`
  ADD CONSTRAINT chain_ibfk_1 FOREIGN KEY (underlyingId) REFERENCES underlying (id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT chain_ibfk_2 FOREIGN KEY (cycleId) REFERENCES cycle (id) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT chain_ibfk_3 FOREIGN KEY (strikeId) REFERENCES strike (id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `earning`
--
ALTER TABLE `earning`
  ADD CONSTRAINT earning_ibfk_1 FOREIGN KEY (eventId) REFERENCES event (id);

--
-- Constraints for table `event`
--
ALTER TABLE `event`
  ADD CONSTRAINT event_ibfk_1 FOREIGN KEY (underlyingId) REFERENCES underlying (id) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `hv`
--
ALTER TABLE `hv`
  ADD CONSTRAINT hv_ibfk_1 FOREIGN KEY (underlyingId) REFERENCES underlying (id);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
