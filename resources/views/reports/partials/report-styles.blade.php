<style>
    .report-header { margin-bottom: 30px; padding-top: 20px; }
    .report-title { font-size: 28px; font-weight: 600; color: #1e293b; margin-bottom: 25px; }
    .report-subtitle { color: #64748b; font-size: 14px; margin-top: -18px; margin-bottom: 20px; }
    .info-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none; border-radius: 12px; padding: 20px; color: white;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .info-card:hover { transform: translateY(-5px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6); }
    .info-card.amount { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .info-card.secondary { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .info-card.success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .info-card.warning { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); color: #1e293b; }
    .info-card.danger { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); }
    .info-card-value { font-size: 32px; font-weight: 700; margin-top: 10px; }
    .info-card-label { font-size: 14px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-card-icon { font-size: 24px; margin-bottom: 10px; }
    .filter-section, .meta-section {
        background: white; padding: 20px; border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); margin-bottom: 25px;
    }
    .filter-section h5, .section-heading {
        color: #1e293b; font-weight: 600; margin-bottom: 15px;
        display: flex; align-items: center; font-size: 16px;
    }
    .filter-section h5 i, .section-heading i { margin-right: 8px; color: #667eea; }
    .filter-options { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
    .filter-btn {
        padding: 8px 16px; border: 2px solid #e2e8f0; background: white; color: #475569;
        border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500;
        transition: all 0.3s ease; text-decoration: none; display: inline-block;
    }
    .filter-btn:hover { border-color: #667eea; color: #667eea; }
    .filter-btn.active { background: #667eea; color: white; border-color: #667eea; }
    .date-range-group { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 15px; }
    .date-input-group { display: flex; gap: 10px; align-items: flex-end; flex: 1; min-width: 300px; }
    .date-input-group label { color: #475569; font-size: 13px; font-weight: 500; margin-bottom: 5px; display: block; }
    .date-input-group input {
        padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px;
    }
    .date-input-group input:focus {
        outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    .btn-filter {
        padding: 8px 20px; background: #667eea; color: white; border: none; border-radius: 6px;
        font-weight: 500; cursor: pointer; font-size: 14px; white-space: nowrap;
    }
    .btn-filter:hover { background: #5568d3; }
    .download-section { display: flex; gap: 10px; flex-wrap: wrap; }
    .btn-download {
        padding: 10px 16px; border: none; border-radius: 6px; font-weight: 500; font-size: 14px;
        display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: white;
    }
    .btn-download-pdf { background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%); }
    .btn-download-excel { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .table-container {
        background: white; border-radius: 10px; overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); overflow-x: auto; margin-bottom: 25px;
    }
    .excel-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .excel-table thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .excel-table th {
        padding: 15px; text-align: left; font-weight: 600; border-bottom: 2px solid #667eea;
        text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;
    }
    .excel-table td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; color: #475569; }
    .excel-table tbody tr:hover { background-color: #f8fafc; }
    .excel-table tbody tr:nth-child(even) { background-color: #f9fafc; }
    .empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; }
    .empty-state i { font-size: 48px; margin-bottom: 15px; color: #cbd5e1; display: block; }
    .badge-pill {
        display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;
    }
    .badge-match { background: #dcfce7; color: #166534; }
    .badge-over { background: #dbeafe; color: #1e40af; }
    .badge-under { background: #fee2e2; color: #991b1b; }
    .badge-low { background: #fef3c7; color: #92400e; }
    .badge-ok { background: #dcfce7; color: #166534; }
    .fill-bar { height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; min-width: 80px; }
    .fill-bar span { display: block; height: 100%; border-radius: 4px; }
    .fill-low span { background: #ef4444; }
    .fill-mid span { background: #f59e0b; }
    .fill-high span { background: #22c55e; }
    .text-profit { color: #16a34a; font-weight: 700; }
    .text-loss { color: #dc2626; font-weight: 700; }
    .pl-summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px; }
    .pl-line {
        display: flex; justify-content: space-between; align-items: center;
        padding: 14px 18px; background: #f8fafc; border-radius: 8px; margin-bottom: 8px;
    }
    .pl-line.total { background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%); border: 2px solid #667eea; font-size: 18px; }
    .row-low { background: #fff7ed !important; }
    @media (max-width: 768px) {
        .info-card { margin-bottom: 15px; }
        .date-input-group { min-width: 100%; }
        .filter-options { flex-direction: column; }
        .filter-btn { width: 100%; text-align: center; }
        .download-section { flex-direction: column; }
        .btn-download { width: 100%; justify-content: center; }
        .report-title { font-size: 22px; }
    }
</style>
