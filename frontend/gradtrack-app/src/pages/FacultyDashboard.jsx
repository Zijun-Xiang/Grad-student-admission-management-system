import React, { useEffect, useState, useContext } from 'react';
import { useNavigate } from 'react-router-dom';
import Layout from '../components/layout/Layout';
import './FacultyDashboard.css';
import DocumentVault from '../components/widgets/DocumentVaultWidget';
import CalendarWidget from '../components/widgets/CalendarWidget';
import DocumentReview from '../components/widgets/AdminDocumentReviewWidget';
import {UserContext} from '../context/UserContext';
import API_CONFIG from '../api/config';

const FacultyDashboard = () => {
  const { user } = useContext(UserContext);
  const [faculty, setFaculty] = useState(null);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  //Fetch faculty data from API
  useEffect(() => {
    const fetchFaculty = async () => {
      setLoading(true);
      try{
        console.log('Fetching faculty data for user:', user.id);
        const response = await API_CONFIG.request(`/api/faculty/${user.id}/students`, {
          method: 'GET',
        });
        const data = await response.json();
        setFaculty(data.faculty);
      }
      catch(error){
        console.error('Error fetching faculty data:', error);
        setFaculty(null);
      }
      finally{
        setLoading(false);
      }
    };
    if(user){
      fetchFaculty();
    }
  }, [user]);

  if (loading) return <div>Loading...</div>;
  if(!faculty) return <div>No faculty data found</div>;

  return (
    <Layout>
      <div className="page-shell faculty-dashboard-page">
        <div className="page-grid wide faculty-grid">
          <div className="card">
            <div className="card-header">
              <span className="card-title">Faculty Overview</span>
              <span className="pill">/api/faculty/{user?.id}/students</span>
            </div>
            <div className="card-body">
              <div className="section-heading">Welcome, {faculty.user.first_name} {faculty.user.last_name}</div>
              <div className="stack">
                <div className="muted">Title: {faculty.title}</div>
                <div className="muted">Office: {faculty.office}</div>
                <div className="pill">Advisees {faculty.advised_students.length}</div>
              </div>
            </div>
          </div>

          <CalendarWidget className="grid-span-2 reminder-prominent" />

          <div className="card">
            <div className="card-header">
              <span className="card-title">Document Review</span>
              <span className="pill">Review</span>
            </div>
            <div className="card-body card-body-flush">
              <DocumentReview />
            </div>
          </div>

          <div className="card table-card">
            <div className="card-header">
              <span className="card-title">Your Advisees</span>
              <span className="pill">Students</span>
            </div>
            <div className="card-body">
              {faculty.advised_students.length === 0 ? (
                <div className="muted">No advisees found.</div>
              ) : (
                <table className="students-table">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Program</th>
                      <th>Started</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    {faculty.advised_students.map(student => (
                      <tr key={student.student_id}>
                        <td>{student.user.first_name} {student.user.last_name}</td>
                        <td>{student.program_type}</td>
                        <td>{student.start_term}</td>
                        <td>
                          <button className="view-btn" onClick={() => navigate(`/student-details/${student.student_id}`)}>View</button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
};

export default FacultyDashboard;
