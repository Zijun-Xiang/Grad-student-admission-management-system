import "./sign.css";
import React, {useState, useContext} from "react";
import logo from "../assets/grad.png";
import { useNavigate } from "react-router-dom";
import { UserContext } from "../context/UserContext";
import API_CONFIG from "../api/config";
import { Link } from "react-router-dom";

export default function Signin() {
    const navigate = useNavigate();
    const { login } = useContext(UserContext);
    const [form, setForm] = useState({
        email:"",
        password: ""
    });
    const [error, setError] = useState("");
    const [loading, setLoading] = useState(false);

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setForm((prev) => ({
            ...prev,
            [name]: type === "checkbox" ? checked : value,
        }));
    };

    const sendSignIn = async (email, password) => {
        setLoading(true);
        setError("");
        
        try {
            const response = await API_CONFIG.request(API_CONFIG.ENDPOINTS.LOGIN, {
                method: 'POST',
                body: JSON.stringify({ email, password }),
            });
            
            const data = await response.json();
            
            // Use UserContext to handle login
            login(data.user, data.token);
            
            // Navigate to dashboard
            if(data.user.role === 'student') {
                navigate('/dashboard');
            } else if(data.user.role === 'faculty') {
                navigate('/faculty-dashboard');
            } else if(data.user.role === 'admin') {
                navigate('/admin-dashboard');
            }
        } catch (error) {
            setError(error.message || 'Login failed. Please try again.');
        } finally {
            setLoading(false);
        }
    }

    return (
        <div className="login-page">
            <div className="card auth-card">
              <div className="card-header">
                <span className="card-title">GradTrack Access</span>
                <span className="pill">Sign In</span>
              </div>
              <div className="card-body">
                <div className="auth-header">
                  <img src={logo} alt="Logo" className="logo" />
                  <div className="auth-title">
                    <h1>Sign In</h1>
                    <p>Continue to your dashboard</p>
                  </div>
                </div>
                <form onSubmit={(e) => {e.preventDefault(); sendSignIn(form.email, form.password)}}>
                    {error && <div className="pill error">{error}</div>}
                    <input type="email" name="email" placeholder="E-mail" value={form.email} onChange={(handleChange)} required/>
                    <input type="password" name="password" placeholder="Password" value={form.password} onChange={(handleChange)} required/>
                    <div className="auth-actions">
                      <Link to="/signup" className="secondary-link">Create account</Link>
                      <button type="submit" disabled={loading}>
                          {loading ? 'Signing In...' : 'Login'}
                      </button>
                    </div>
                </form>
              </div>
            </div>
        </div>
    );
}
