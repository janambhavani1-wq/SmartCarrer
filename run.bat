@echo off
title CareerCompass - AI Smart Career Recommendation System
echo ======================================================================
echo    CareerCompass - AI-Powered Smart Career Recommendation System
echo ======================================================================
echo.
echo [1/3] Checking dependencies...
python -m pip install -r requirements.txt --quiet
echo [2/3] Verifying Database and Seeding Data...
python database.py
echo [3/3] Starting CareerCompass Web Server...
echo.
echo Access URL: http://127.0.0.1:5000
echo Demo Admin: admin@careercompass.com / Admin@12345
echo Demo Student: student@careercompass.com / Student@12345
echo.
echo Press Ctrl+C in this window to stop the server.
echo ======================================================================
python app.py
pause
