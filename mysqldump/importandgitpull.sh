cd /var/www/html/mahisfashionparadise
echo pull changes

git pull
cd /var/www/html/mahisfashionparadise/mysqldump
echo import sql db

sed -i 's/localhost/192.168.1.236/g' mahisfashiondb.sql

mysql -f --user=root --password=sh22ee05 mahisfashiondb < mahisfashiondb.sql
echo Done!
