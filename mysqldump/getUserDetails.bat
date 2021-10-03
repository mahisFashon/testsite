@echo off 
echo "sep=\t" > userDetails.csv
C:\MAMP\bin\mysql\bin\mysql -f --user=root --password=root mahisfashiondb < getUserDetails.sql >> userDetails.csv
echo Done!
pause
exit