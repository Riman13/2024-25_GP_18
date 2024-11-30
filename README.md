# 2024-25_GP_18
# Doroob - Personalized Tourism Recommender System

## Introduction

Doroob is a personalized recommendation platform designed to guide users to preferred tourist destinations in Riyadh. 
It uses a hybrid recommendation system incorporating facial emotion recognition (FER) to provide tailored suggestions based on user preferences, emotions, and location data.

## Technology Stack

- **Python** (Backend and Machine Learning)
- **TensorFlow** & **DeepFace** (Facial Emotion Recognition)
- **HTML**, **CSS**, **JavaScript** (Frontend)
- **GitHub** (Version Control)
- **Jira** (Agile Project Management Tool)
- **Anaconda** (Python Distribution and Environment Management)
  
## Launch Instructions

Instructions for Setting Up and Running the Doroob System
1. Install Anaconda
2. Import a Doroob  Environment
•	Open Anaconda interface 
•	Go to Environment section
•	Click on import
•	Download doroob_enviroment.yml from github in your desktop
•	From local drive choose the path of doroob_enviroment.yml 
•	Give the name as doroob_enviroment 
•	Then click on import
3. After successfully importing from home section on Anaconda interface install and lunch the powershell
4.then run your server mamp or xmamp and To connect to the MySQL server you should modify config.php file based on your server information
5.import doroob.sql in phpMyAdmin.
6.After you running the server you can open the doroob system
7. You need to open three separate PowerShell windows to run the recommendation algorithms. In each window, start by typing:
•	conda activate doroob_enviroment  
•	then write cd <path_of_Doroob_system_folder> ex.C:\Users\UserName\OneDrive\سطح المكتب\2024-25_GP_18\Doorob
•	In the first window, write: python CFRS.py
•	In the second window, write: python Context-Content.py
•	In the third window, write: python lightFM.py
8. then you can see the recommendation in the homepage website and you can see the evaluation in the powershell

