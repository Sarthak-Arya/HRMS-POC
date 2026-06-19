<style>
    .employee-card .card {
        background-color: var(--hrms-card-bg, #ffffff);
        border-radius: 8px;
        box-shadow: 0 2px 4px var(--hrms-shadow, rgba(0, 0, 0, 0.1));
        padding: 20px;
        width: 300px;
        color: var(--hrms-text-primary, #344767);
    }

    .employee-card .card-header {
        display: flex;
        align-items: center;
    }

    .employee-card .profile-pic {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 15px;
    }

    .employee-card .name {
        font-size: 18px;
        font-weight: bold;
        margin: 0;
    }

    .employee-card .job-title {
        color: var(--hrms-text-secondary, #666);
    }

    .employee-card .badges {
        display: flex;
        margin-bottom: 15px;
    }

    .employee-card .badge {
        font-size: 12px;
        padding: 5px 10px;
        border-radius: 15px;
        margin-right: 10px;
    }

    .employee-card .badge-purple {
        background-color: #ffe6e6;
        color: red;
    }

    .employee-card .badge-gray {
        background-color: var(--hrms-hover-bg, #f0f0f0);
        color: var(--hrms-text-secondary, #666);
    }

    .employee-card .info {
        font-size: 14px;
        color: var(--hrms-text-secondary, #666);
        margin: 5px 0;
    }
</style>
<div class="employee-card">

    <div class="card">
        <div class="card-header">
            {{-- <img src="https://hebbkx1anhila5yf.public.blob.vercel-storage.com/card-NbQSeOyJSKxpEYeFhPP6HaYHy5AfvA.jpg"
                alt="Esther Howard" class="profile-pic"> --}}
            <div>
                <h2 class="name">Esther Howard</h2>
                <p class="job-title">Developer</p>
            </div>
        </div>
        <div class="badges">
            <span class="badge badge-purple">Developer</span>
            <span class="badge badge-gray">Management</span>
        </div>
        <p class="info">Emp Code: 01102021-786</p>
        <p class="info">Joining Date: 01-Jan-2021</p>
    </div>    
</div>
