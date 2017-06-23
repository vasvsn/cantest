-- phpMyAdmin SQL Dump
-- version 4.6.6
-- Generation Time: Jun 23, 2017 at 02:34 AM
-- Server version: 5.7.9
-- PHP Version: 5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `p2p`
--
CREATE DATABASE IF NOT EXISTS `p2p` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `p2p`;

-- --------------------------------------------------------

--
-- Table structure for table `assessors`
--

DROP TABLE IF EXISTS `assessors`;
CREATE TABLE IF NOT EXISTS `assessors` (
  `studentID` int(11) NOT NULL,
  `factID` int(11) NOT NULL,
  `grade` int(11) DEFAULT NULL,
  `comment` text,
  `gradeDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`studentID`,`factID`),
  KEY `factID` (`factID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `facts`
--

DROP TABLE IF EXISTS `facts`;
CREATE TABLE IF NOT EXISTS `facts` (
  `factID` int(11) NOT NULL AUTO_INCREMENT,
  `studentID` int(11) NOT NULL,
  `goalID` int(11) NOT NULL,
  `weekNr` int(11) DEFAULT NULL,
  `description` text,
  `avgGrade` decimal(4,2) DEFAULT NULL,
  `status` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`factID`),
  KEY `studentID` (`studentID`),
  KEY `goalID` (`goalID`)
) ENGINE=InnoDB AUTO_INCREMENT=309 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `goals`
--

DROP TABLE IF EXISTS `goals`;
CREATE TABLE IF NOT EXISTS `goals` (
  `goalID` int(11) NOT NULL AUTO_INCREMENT,
  `shortText` varchar(40) DEFAULT NULL,
  `description` text,
  `type` varchar(45) NOT NULL,
  PRIMARY KEY (`goalID`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
CREATE TABLE IF NOT EXISTS `grades` (
  `gradeID` int(11) NOT NULL AUTO_INCREMENT,
  `studentID` int(11) NOT NULL,
  `goalID` int(11) NOT NULL,
  `weekNr` int(11) NOT NULL,
  `grade` decimal(4,1) DEFAULT NULL,
  PRIMARY KEY (`studentID`,`goalID`,`weekNr`,`gradeID`) USING BTREE,
  UNIQUE KEY `gradeId_index` (`gradeID`),
  KEY `goalID` (`goalID`)
) ENGINE=InnoDB AUTO_INCREMENT=287 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
CREATE TABLE IF NOT EXISTS `students` (
  `studentID` int(11) NOT NULL AUTO_INCREMENT,
  `studentName` varchar(40) NOT NULL,
  `studentEmail` varchar(40) NOT NULL,
  `studentPassword` varchar(32) NOT NULL,
  `studentImageUrl` text NOT NULL,
  `studentStudy` varchar(45) NOT NULL,
  PRIMARY KEY (`studentID`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
CREATE TABLE IF NOT EXISTS `tags` (
  `factID` int(11) NOT NULL,
  `hashtags` text,
  `frameworks` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`factID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assessors`
--
ALTER TABLE `assessors`
  ADD CONSTRAINT `assessors_ibfk_1` FOREIGN KEY (`studentID`) REFERENCES `students` (`studentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `assessors_ibfk_2` FOREIGN KEY (`factID`) REFERENCES `facts` (`factID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `facts`
--
ALTER TABLE `facts`
  ADD CONSTRAINT `facts_ibfk_1` FOREIGN KEY (`studentID`) REFERENCES `students` (`studentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `facts_ibfk_2` FOREIGN KEY (`goalID`) REFERENCES `goals` (`goalID`) ON DELETE NO ACTION ON UPDATE CASCADE;

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`studentID`) REFERENCES `students` (`studentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`goalID`) REFERENCES `goals` (`goalID`) ON DELETE NO ACTION ON UPDATE CASCADE;

--
-- Constraints for table `tags`
--
ALTER TABLE `tags`
  ADD CONSTRAINT `tags_ibfk_1` FOREIGN KEY (`factID`) REFERENCES `facts` (`factID`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
