/**
 * CareerCompass – Main Client-Side JavaScript
 * Controls UI interactions, modals, notifications, range sliders, and dynamic quizzes.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Navigation Toggle
    const mobileToggle = document.getElementById('mobileToggle');
    const navLinks = document.getElementById('navLinks');
    if (mobileToggle && navLinks) {
        mobileToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }

    // 2. Auto Dismiss Flash Alerts after 5 seconds
    const alerts = document.querySelectorAll('.flash-alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(50px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // 3. Interactive Range Slider Real-Time Badges
    const rangeSliders = document.querySelectorAll('input[type="range"].skill-range');
    rangeSliders.forEach(slider => {
        const badge = document.getElementById(`badge_${slider.dataset.skillId}`);
        const updateBadge = () => {
            const val = parseInt(slider.value);
            const labels = {
                1: '1/5 - Novice',
                2: '2/5 - Beginner',
                3: '3/5 - Intermediate',
                4: '4/5 - Advanced',
                5: '5/5 - Expert'
            };
            if (badge) {
                badge.textContent = labels[val] || `${val}/5`;
            }
        };
        slider.addEventListener('input', updateBadge);
        updateBadge();
    });

    // 4. Modal Handlers
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    };

    // Close modal on backdrop click
    document.querySelectorAll('.modal-backdrop').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        });
    });

    // 5. Quiz Timer Engine (for Skill Assessment)
    const quizForm = document.getElementById('quizForm');
    const timerDisplay = document.getElementById('quizTimer');
    if (quizForm && timerDisplay) {
        let timeLeft = 10 * 60; // 10 minutes
        const timerInterval = setInterval(() => {
            timeLeft--;
            const mins = Math.floor(timeLeft / 60);
            const secs = timeLeft % 60;
            timerDisplay.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;

            if (timeLeft <= 60) {
                timerDisplay.style.color = '#f43f5e';
            }

            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                alert('Time is up! Submitting your skill assessment now.');
                quizForm.submit();
            }
        }, 1000);
    }

    // 6. Admin Student Inspection Modal AJAX Loader
    window.inspectStudent = function(studentId) {
        openModal('studentDetailModal');
        const modalBody = document.getElementById('studentDetailContent');
        if (!modalBody) return;

        modalBody.innerHTML = `
            <div style="text-align: center; padding: 2rem;">
                <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--accent-cyan);"></i>
                <p style="margin-top: 0.75rem; color: var(--text-secondary);">Loading student dossier...</p>
            </div>
        `;

        fetch(`/admin/students/${studentId}/details`)
            .then(res => res.json())
            .then(data => {
                const p = data.profile || {};
                const r = data.recommendation || {};
                const skills = data.skills || [];

                let skillsHtml = skills.map(s => `
                    <span class="skill-pill matched">
                        ${s.name} (${s.proficiency_level}/5)
                    </span>
                `).join('') || '<span style="color: var(--text-muted);">No skills rated yet.</span>';

                modalBody.innerHTML = `
                    <div style="display: flex; gap: 1.25rem; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-glass);">
                        <div class="brand-icon" style="width: 3.5rem; height: 3.5rem; font-size: 1.5rem;">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div>
                            <h3 style="font-size: 1.35rem;">${p.name || 'Student'}</h3>
                            <p style="color: var(--text-secondary); font-size: 0.9rem;">${p.email} | ${p.phone || 'No Phone'}</p>
                            <span class="role-tag" style="margin-top: 0.25rem; display: inline-block;">${p.education_level || 'Undergraduate'} • ${p.field_of_study || 'General'}</span>
                        </div>
                    </div>

                    <div class="form-grid-2" style="margin-bottom: 1.25rem;">
                        <div class="glass-card" style="padding: 1rem;">
                            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Institution & CGPA</div>
                            <div style="font-weight: 600; margin-top: 0.25rem;">${p.institution || 'N/A'} (CGPA: ${p.cgpa_percentage || 'N/A'})</div>
                        </div>
                        <div class="glass-card" style="padding: 1rem;">
                            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Aspiration & Work Style</div>
                            <div style="font-weight: 600; margin-top: 0.25rem;">${p.dream_role || 'Undecided'} (${p.preferred_work_env || 'Hybrid'})</div>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <h4 style="font-size: 0.95rem; margin-bottom: 0.5rem; color: var(--accent-cyan);"><i class="fas fa-tools"></i> Evaluated Skills</h4>
                        <div class="skills-pill-wrap">${skillsHtml}</div>
                    </div>

                    <div>
                        <h4 style="font-size: 0.95rem; margin-bottom: 0.5rem; color: var(--accent-primary);"><i class="fas fa-magic"></i> AI Recommendation Match</h4>
                        ${r.top_career_title ? `
                            <div class="glass-card" style="padding: 1rem; border-color: var(--border-glass-bright);">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <strong>${r.top_career_title}</strong>
                                    <span class="status-badge status-replied">${r.match_percentage}% Match</span>
                                </div>
                                <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.5rem;">
                                    Avg. Salary: <strong>${r.salary_forecast || 'Competitive'}</strong>
                                </div>
                            </div>
                        ` : '<p style="color: var(--text-muted); font-size: 0.875rem;">No recommendation generated yet.</p>'}
                    </div>
                `;
            })
            .catch(err => {
                modalBody.innerHTML = `<div style="color: #f43f5e; padding: 1rem;">Error loading student dossier.</div>`;
            });
    };
});
