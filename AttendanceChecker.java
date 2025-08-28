import java.io.*;
import java.util.*;
import java.util.regex.*;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;

class Student {
    String name;
    String enrollmentId;
    String course;
    String date;
    String status;

    Student(String name, String enrollmentId, String course, String date, String status) {
        this.name = name;
        this.enrollmentId = enrollmentId;
        this.course = course;
        this.date = date;
        this.status = status;
    }
}

public class AttendanceChecker {
    public static void main(String[] args) {
        String fileName = "attendance.txt"; // input file
        List<Student> attendanceList = new ArrayList<>();

        Pattern headerPattern = Pattern.compile("(BSC|BCA)\\s*\\((\\d{2}/\\d{2}/\\d{2})\\)");
        Pattern absentPattern = Pattern.compile("Absent\\((\\d{1,2} [A-Za-z]+)\\)");
        Pattern studentPattern = Pattern.compile("\\d*\\.?\\s*([A-Za-z .]+)[-\\(]*\\s*(\\d{10})[\\)]*");


        String currentCourse = null;
        String currentDate = null;
        String currentStatus = "Present";

        DateTimeFormatter formatter = DateTimeFormatter.ofPattern("dd/MM/yy");
        LocalDate startDate = LocalDate.parse("04/08/25", formatter);

        try (BufferedReader br = new BufferedReader(new FileReader(fileName))) {
            String line;
            while ((line = br.readLine()) != null) {
                line = line.trim();
                if (line.isEmpty() || line.contains("Media omitted") || line.contains("changed this group")) continue;

                Matcher hm = headerPattern.matcher(line);
                if (hm.find()) {
                    currentCourse = hm.group(1);
                    currentDate = hm.group(2);
                    LocalDate classDate = LocalDate.parse(currentDate, formatter);
                    if (classDate.isBefore(startDate)) currentCourse = null;
                    currentStatus = "Present";
                    continue;
                }

                Matcher am = absentPattern.matcher(line);
                if (am.find() && currentCourse != null) {
                    currentDate = am.group(1);
                    currentStatus = "Absent";
                    continue;
                }

                Matcher sm = studentPattern.matcher(line);
                if (sm.find() && currentCourse != null) {
                    String name = sm.group(1).trim();
                    String id = sm.group(2).trim();
                    attendanceList.add(new Student(name, id, currentCourse, currentDate, currentStatus));
                }
            }
        } catch (IOException e) {
            System.out.println("❌ Error reading file: " + e.getMessage());
        }

        // Show All Data Separate for BCA and BSC
        showAllDataSeparate(attendanceList);
    }

    private static void showAllDataSeparate(List<Student> list) {
        showFullMatrix(list, "BCA");
        showFullMatrix(list, "BSC");
    }

    private static void showFullMatrix(List<Student> list, String course) {
        System.out.println("\n--- Attendance : " + course + " ---");

        Map<String, String> names = new HashMap<>();
        Set<String> dates = new TreeSet<>();
        Set<String> enrollIds = new TreeSet<>();
        Map<String, Map<String, String>> matrix = new HashMap<>();

        for (Student s : list) {
            if (!s.course.equalsIgnoreCase(course)) continue;
            names.put(s.enrollmentId, s.name);
            dates.add(s.date);
            enrollIds.add(s.enrollmentId);
            matrix.putIfAbsent(s.enrollmentId, new HashMap<>());
            matrix.get(s.enrollmentId).put(s.date, s.status.equalsIgnoreCase("Present") ? "P" : "A");
        }

        // Header
        System.out.printf("%-25s %-12s", "Name", "Enroll ID");
        for (String d : dates) System.out.printf(" %-8s", d);
        System.out.printf(" %-8s%n", "Percentage %");
        System.out.println("-------------------------------------------------------------------------------------------------------------");

        for (String id : enrollIds) {
            System.out.printf("%-25s %-12s", names.get(id), id);
            Map<String, String> att = matrix.get(id);
            int presentCount = 0;
            for (String d : dates) {
                String status = att.getOrDefault(d, "-");
                if (status.equals("P")) presentCount++;
                System.out.printf(" %-8s", status);
            }
            double percent = dates.size() > 0 ? (presentCount * 100.0 / dates.size()) : 0;
            System.out.printf(" %-8.2f%n", percent);
        }
    }
}
