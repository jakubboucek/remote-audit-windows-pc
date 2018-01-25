@ECHO off
wmic bios get serialnumber > %1
wmic csproduct get name >> %1