import java.io.*;
import java.util.*;
import java.util.regex.*;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;

class Student {
    String enrollmentId, course, date, status;

    Student(String enrollmentId, String course, String date, String status) {
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

        // Regex patterns
        Pattern headerPattern = Pattern.compile("(\\d{2}/\\d{2}/\\d{2})");
        Pattern absentPattern = Pattern.compile("Absent\\((\\d{1,2} [A-Za-z]+)\\)");
        Pattern studentPattern = Pattern.compile("\\d*\\.?\\s*([A-Za-z .]+)?[-\\(]*\\s*(\\d{10})[\\)]*");

        String currentDate = null;
        String currentStatus = "Present";

        DateTimeFormatter formatter = DateTimeFormatter.ofPattern("dd/MM/yy");
        LocalDate startDate = LocalDate.parse("04/08/25", formatter); // ignore before this date

        try (BufferedReader br = new BufferedReader(new FileReader(fileName))) {
            String line;
            while ((line = br.readLine()) != null) {
                line = line.trim();
                if (line.isEmpty() || line.contains("Media omitted") || line.contains("changed this group")) 
                    continue;

                // Check date line
                Matcher hm = headerPattern.matcher(line);
                if (hm.find()) {
                    currentDate = hm.group(1);
                    LocalDate classDate = LocalDate.parse(currentDate, formatter);
                    if (classDate.isBefore(startDate)) {
                        currentDate = null; // skip old dates
                        continue;
                    }
                    currentStatus = "Present";
                    continue;
                }

                // Check absent marker
                Matcher am = absentPattern.matcher(line);
                if (am.find() && currentDate != null) {
                    currentStatus = "Absent";
                    continue;
                }

                // Check student line
                Matcher sm = studentPattern.matcher(line);
                if (sm.find() && currentDate != null) {
                    String id = sm.group(2).trim();

                    // Decide course
                    String course = (id.compareTo("2311010051") >= 0 && id.compareTo("2311010066") <= 0)
                            ? "BCA" : "CSC";

                    attendanceList.add(new Student(id, course, currentDate, currentStatus));
                }
            }
        } catch (IOException e) {
            System.out.println("❌ Error reading file: " + e.getMessage());
        }

        // Print attendance
        showAttendanceMatrix(attendanceList, "BCA");
        showAttendanceMatrix(attendanceList, "CSC");
    }

    private static void showAttendanceMatrix(List<Student> list, String course) {
        System.out.println("\n--- OOP Attendance 5th Semester(Major): " + course + " ---");

        DateTimeFormatter formatter = DateTimeFormatter.ofPattern("dd/MM/yy");
        Set<LocalDate> dates = new TreeSet<>();
        Set<String> enrollIds = new TreeSet<>();
        Map<String, Map<LocalDate, String>> matrix = new HashMap<>();

        // Build matrix
        for (Student s : list) {
            if (!s.course.equalsIgnoreCase(course)) continue;

            LocalDate d = LocalDate.parse(s.date, formatter);
            dates.add(d);
            enrollIds.add(s.enrollmentId);

            matrix.putIfAbsent(s.enrollmentId, new HashMap<>());
            matrix.get(s.enrollmentId).put(d, s.status.equalsIgnoreCase("Present") ? "P" : "A");
        }

        // Header row
        System.out.printf("%-12s", "Enroll ID");
        for (LocalDate d : dates) System.out.printf(" %-8s", d.format(formatter));
        System.out.printf(" %-8s%n", "%");

        // Separator line (dynamic length)
        int totalCols = dates.size() + 2;
        System.out.println("-".repeat(totalCols * 9));

        // Data rows
        for (String id : enrollIds) {
            System.out.printf("%-12s", id);
            Map<LocalDate, String> att = matrix.get(id);
            int presentCount = 0;
            for (LocalDate d : dates) {
                String status = att.getOrDefault(d, "-");
                if (status.equals("P")) presentCount++;
                System.out.printf(" %-8s", status);
            }
            double percent = dates.size() > 0 ? (presentCount * 100.0 / dates.size()) : 0;
            System.out.printf(" %-8.2f%n", percent);
        }
    }
}
