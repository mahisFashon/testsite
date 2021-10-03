cd /var/www/html/mahisfashionparadise/mysqldump
echo db dump started

mysqldump --insert-ignore --user=root --password=sh22ee05 mahisfashiondb > mahisfashiondb.sql
sed -i 's/192.168.1.236/localhost/g' mahisfashiondb.sql
echo db dump done

cd /var/www/html/mahisfashionparadise/

echo add updated files
git add *
echo commit changes

git commit -a -m "latest change added in  db"

echo push changes

git push 

echo Done!
