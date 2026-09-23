/**
 * CareerCompass – Chart.js Visualizations & Analytics
 * Handles Radar Charts, Match Breakdown, and Admin Trends.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Student Skills Radar Chart
    const radarCtx = document.getElementById('studentSkillRadarChart');
    if (radarCtx) {
        fetch('/api/student/skill-radar')
            .then(res => res.json())
            .then(data => {
                new Chart(radarCtx, {
                    type: 'radar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Your Evaluated Proficiency (%)',
                            data: data.data,
                            backgroundColor: 'rgba(99, 102, 241, 0.25)',
                            borderColor: '#6366f1',
                            pointBackgroundColor: '#06b6d4',
                            pointBorderColor: '#ffffff',
                            pointHoverBackgroundColor: '#ffffff',
                            pointHoverBorderColor: '#6366f1',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            r: {
                                angleLines: { color: 'rgba(255, 255, 255, 0.1)' },
                                grid: { color: 'rgba(255, 255, 255, 0.08)' },
                                pointLabels: {
                                    color: '#94a3b8',
                                    font: { size: 12, family: "'Plus Jakarta Sans', sans-serif" }
                                },
                                ticks: {
                                    backdropColor: 'transparent',
                                    color: '#64748b',
                                    stepSize: 20,
                                    min: 0,
                                    max: 100
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: { color: '#f8fafc', font: { family: "'Plus Jakarta Sans', sans-serif" } }
                            }
                        }
                    }
                });
            })
            .catch(err => console.error('Radar chart load error:', err));
    }

    // 2. Admin Dashboard Charts
    const adminDemandCtx = document.getElementById('adminCareerDemandChart');
    const adminCategoryCtx = document.getElementById('adminCategoryPieChart');

    if (adminDemandCtx || adminCategoryCtx) {
        fetch('/api/admin/analytics')
            .then(res => res.json())
            .then(data => {
                if (adminDemandCtx && data.top_recommended) {
                    const titles = data.top_recommended.map(r => r.top_career_title);
                    const counts = data.top_recommended.map(r => r.count);

                    new Chart(adminDemandCtx, {
                        type: 'bar',
                        data: {
                            labels: titles.length ? titles : ['AI Engineer', 'Full Stack Dev', 'Data Scientist', 'DevOps', 'Cybersecurity'],
                            datasets: [{
                                label: 'Student Recommendations Generated',
                                data: counts.length ? counts : [12, 9, 8, 6, 5],
                                backgroundColor: [
                                    'rgba(99, 102, 241, 0.8)',
                                    'rgba(6, 182, 212, 0.8)',
                                    'rgba(16, 185, 129, 0.8)',
                                    'rgba(245, 158, 11, 0.8)',
                                    'rgba(244, 63, 94, 0.8)'
                                ],
                                borderRadius: 8,
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: { color: '#94a3b8' }
                                },
                                y: {
                                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                                    ticks: { color: '#94a3b8', stepSize: 1 }
                                }
                            }
                        }
                    });
                }

                if (adminCategoryCtx && data.career_categories) {
                    const categories = Object.keys(data.career_categories);
                    const catCounts = Object.values(data.career_categories);

                    new Chart(adminCategoryCtx, {
                        type: 'doughnut',
                        data: {
                            labels: categories,
                            datasets: [{
                                data: catCounts,
                                backgroundColor: [
                                    '#6366f1',
                                    '#06b6d4',
                                    '#10b981',
                                    '#f59e0b',
                                    '#ec4899',
                                    '#8b5cf6'
                                ],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: '#94a3b8', font: { size: 11 } }
                                }
                            },
                            cutout: '70%'
                        }
                    });
                }
            })
            .catch(err => console.error('Admin chart load error:', err));
    }
});
