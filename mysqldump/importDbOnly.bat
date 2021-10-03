@echo off 
cd C:\MAMP\htdocs\mahisfashionparadise\mysqldump
echo import sql db

C:\MAMP\bin\mysql\bin\mysql -f --user=root --password=root mahisfashiondb < mahisfashiondb.sql
echo Done!
pause
exit