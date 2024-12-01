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

## Setup Instructions
### Instructions for Setting Up and Running the Doroob System

1. **Install Anaconda**  
2. **Import the Doroob Environment**  
   - Open the Anaconda interface.  
   - Go to the "Environment" section.  
   - Click on "Import."  
   - Download `doroob_enviroment.yml` from the GitHub repository to your desktop.  
   - From the local drive, choose the path of `doroob_enviroment.yml`.  
   - Give the name as `doroob_enviroment`.  
   - Click on "Import."  

3. **Install and Launch PowerShell**  
   - After successfully importing, go to the "Home" section in the Anaconda interface.  
   - Install and launch PowerShell.  

4. **Run Your Server**  
   - Use MAMP or XAMPP to start your server.  
   - Modify the `config.php` file based on your server information to connect to the MySQL server.  

5. **Import the Database**  
   - Import `doroob.sql` in phpMyAdmin.  

6. **Run the Doroob System**  
   - After starting the server, you can open the Doroob system.  

7. **Run Recommendation Algorithms**  
   - Open three separate PowerShell windows and follow these steps in each:  
     1. Activate the Doroob environment:  
        ```bash
        conda activate doroob_enviroment
        ```  
     2. Navigate to the Doroob system folder:  
        ```bash
        cd <path_of_Doroob_system_folder>
        # Example:
        cd C:\Users\UserName\OneDrive\سطح المكتب\2024-25_GP_18\Doorob
        ```  
     3. Run the corresponding script in each window:  
        - **First window**:  
          ```bash
          python CFRS.py
          ```  
        - **Second window**:  
          ```bash
          python Context-Content.py
          ```  
        - **Third window**:  
          ```bash
          python lightFM.py
          ```  

8. **View Results**  
   - You can see recommendations on the homepage of the website.  
   - Evaluation results will appear in the PowerShell windows.  


