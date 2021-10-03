#mysqldump --insert-ignore --user=root --password=sh22ee05 fabfashn > fabfashndb.sql
#cp fabfashndb.sql fabfashndb.sql.bak
#sed -i 's/192.168.1.236\\\\\/fabfashn/13.232.43.101\\\\\/fabfashn\\\\\/web/g' fabfashndb.sql
#sed -i 's/localhost\\\\\/fabfashn/13.232.43.101\\\\\/fabfashn\\\\\/web/g' fabfashndb.sql
sed -i 's/localhost/192.168.1.236/g' mahisfashiondb.sql
