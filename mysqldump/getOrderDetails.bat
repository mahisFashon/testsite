@echo off 
echo "sep=\t" > orderDetails.csv
C:\MAMP\bin\mysql\bin\mysql -f --user=root --password=root mahisfashiondb < getOrderDetails.sql >> orderDetails.csv
echo Done!
pause
exit