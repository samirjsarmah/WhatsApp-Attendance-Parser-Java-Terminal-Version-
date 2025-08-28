# 📊 WhatsApp Attendance Parser (Java – Terminal Version)

This project is a **Java-based attendance management tool** that parses exported **WhatsApp group chats** (in `.txt` format) and generates an **attendance matrix** directly in the **terminal**.  

It was designed to simplify attendance tracking for semester classes where teachers and students often post attendance records in WhatsApp groups.  

---

## 🚀 Features

- ✅ Parses **WhatsApp chat export (`.txt`)** directly  
- ✅ Extracts **course (BCA/BSC), date, student name, enrollment ID, and status (Present/Absent)**  
- ✅ Displays a **terminal-based attendance matrix** (rows = students, columns = dates)  
- ✅ Calculates **attendance percentage** for each student automatically  
- ✅ Filters out old dates (configurable start date in code)  

---

## 🖼️ Example Output

When run, it prints a **matrix per course** in the terminal:


---

---

## ⚙️ How to Run

1. Install Java (JDK 17 or later recommended).  
   Verify installation:
   ```bash
   java -version
javac AttendanceChecker.java
java AttendanceChecker
