# 2024-25_GP_18
# Doroob - Personalized Tourism Recommender System

## Introduction
Doroob is a personalized recommendation platform designed to guide users to preferred tourist destinations in Riyadh. 
It uses a hybrid recommendation system incorporating facial emotion recognition (FER) to provide tailored suggestions based on user preferences, emotions, and location data.

## Technology Stack
- Python (Backend and Machine Learning)
- TensorFlow & DeepFace (Facial Emotion Recognition)
- HTML, CSS, JavaScript (Frontend)
- GitHub (Version Control)
- Jira (agile project management tool)
-Anaconda
  
## Launch Instructions

Instructions for Setting Up and Running the Doroob System
1. Install Anaconda
2. Import a Doroob  Environment
•	Open Anaconda interface 
•	Go to Environment section
•	Click on import
•	Download CF_environment.yml from github in your desktop
•	From local drive choose the path of CF_environment.yml 
•	Give the name as CF_Environment 
•	Then click on import
3. After successfully importing from home section on Anaconda interface install and lunch the powershell
4.then run your server mamp or xmamp and To connect to the MySQL server you should modify config.php file based on your server information
5.import doroob.sql in phpMyAdmin.
6.After you running the server you can open the doroob system
7. then open the powershell and write the following:
•	conda activate CF_Environment 
•	then write cd <path_of_Doroob_system_folder> ex.C:\Users\UserName\OneDrive\سطح المكتب\2024-25_GP_18\Doorob
•	write python CFRS.py, then you can see the recommendation in the homepage website and you can see the evaluation in the powershell

