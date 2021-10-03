@echo off 
cd C:\MAMP\htdocs\testsite\mysqldump
echo db dump started

C:\MAMP\bin\mysql\bin\mysqldump --insert-ignore --user=root --password=root testsitedb > testsitedb.sql
echo db dump done

cd C:\MAMP\htdocs\testsite

echo add updated files
git add *
echo commit changes

git commit -a -m "latest change added in  db"

echo push changes

git push

echo Done!
pause
exit