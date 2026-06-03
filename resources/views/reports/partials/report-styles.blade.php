<style>
    .report-title { font-size: 28px; font-weight: 600; color: #1e293b; margin-bottom: 10px; }
    .info-card { border-radius: 12px; padding: 20px; color: white; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.25); }
    .info-card.amount { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .info-card.records { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .info-card.expense { background: linear-gradient(135deg, #ef4444 0%, #f97316 100%); }
    .info-card.stock { background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%); }
    .info-card-value { font-size: 28px; font-weight: 700; margin-top: 8px; }
    .info-card-label { font-size: 13px; opacity: 0.9; text-transform: uppercase; }
    .filter-section { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 25px; }
    .filter-options { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px; }
    .filter-btn { padding: 8px 16px; border: 2px solid #e2e8f0; background: white; border-radius: 8px; cursor: pointer; }
    .filter-btn.active { background: #667eea; color: white; border-color: #667eea; }
    .date-range-group { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
    .btn-filter { padding: 8px 20px; background: #667eea; color: white; border: none; border-radius: 6px; }
    .download-section { display: flex; gap: 10px; flex-wrap: wrap; }
    .btn-download { padding: 10px 16px; border-radius: 6px; color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .btn-download-pdf { background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%); }
    .btn-download-excel { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .table-container { background: white; border-radius: 10px; overflow-x: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; }
    .excel-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .excel-table thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .excel-table th, .excel-table td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; }
    .empty-state { text-align: center; padding: 30px; color: #94a3b8; }
</style>
