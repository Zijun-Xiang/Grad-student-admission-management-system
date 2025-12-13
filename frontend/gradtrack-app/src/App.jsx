import { BrowserRouter, Routes, Route } from "react-router-dom";
import { UserProvider } from "./context/UserContext";
import Dashboard from './pages/Dashboard';
import Signin from "./pages/signin";
import Signup from "./pages/signup";
import Milestones from './pages/Milestones';
import Documents from './pages/Documents';
import Settings from './pages/Settings';
import Courses from './pages/Courses';
import FacultyDashboard from './pages/FacultyDashboard';
import AdminDashboard from './pages/AdminDashboard';
import RemindersPage from './pages/RemindersPage';
import AdminDocumentReview from './pages/AdminDocumentReview';
import StudentDetails from './pages/StudentDetails';
import CoursePlanner from './pages/coursePlanner';
import './App.css';

function App() {
    return (
        <UserProvider>
        <BrowserRouter>
            <div className="app-shell">
                <Routes>
                    <Route path="/" element={<Dashboard />} /> 
                    <Route path="/dashboard" element={<Dashboard />} />
                    <Route path="/faculty-dashboard" element={<FacultyDashboard />} />
                    <Route path="/admin-dashboard" element={<AdminDashboard />} /> 
                    <Route path="/admin/documents" element={<AdminDocumentReview />} />
                    <Route path="/signin" element={<Signin />} />
                    <Route path="/signup" element={<Signup />} />
                    <Route path="/milestones" element={<Milestones />} />
                    <Route path="/documents" element={<Documents />} />
                    <Route path="/courses" element={<Courses />} />
                    <Route path="/settings" element={<Settings />} />
                    <Route path="/reminders" element={<RemindersPage />} />
                    <Route path="/student-details/:studentId" element={<StudentDetails />} />
                    <Route path="/course-planner" element={<CoursePlanner />} />
                    {/* <Route path="/profile" element={<UserProfile />} />*/}
                </Routes>
            </div>
        </BrowserRouter>
        </UserProvider>
    );
}

export default App;
