<?php
date_default_timezone_set('Asia/Jakarta');
?>

<!-- Modal -->
<div class="modal fade" id="add_user" tabindex="-1" aria-labelledby="exampleModalLabel">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Edit Surat Tagihan Pajak</h5>
            </div>
            <div class="modal-body">
                <div class="col-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <form class="forms-sample" enctype="multipart/form-data"
                                action="<?= base_url('Beranda/EditSTP') ?>" method="post">
                                <input hidden required type="text" class="form-control" id="ID" name="ID"
                                    placeholder="TransactionNo">
                                <div class="row">
                                    <div class="col-md-12 pr-1">
                                        <div class="form-group">
                                            <label>TransactionNo</label>
                                            <input required type="text" readonly class="form-control" id="TransactionNo"
                                                name="TransactionNo" placeholder="TransactionNo">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 pr-1">
                                        <div class="form-group">
                                            <label>Transaction Date</label>
                                            <input required type="date" id="TransactionDate" name="TransactionDate"
                                                class="form-control" placeholder="Tanggal">
                                        </div>
                                    </div>
                                    <div class="col-md-6 pl-1">
                                        <div class="form-group">
                                            <label>Due Date</label>
                                            <input required type="date" id="DueDate" name="DueDate" class="form-control"
                                                placeholder="Jatuh Tempo">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 pr-1">
                                        <div class="form-group">
                                            <label>Nominal</label>
                                            <input type="number" id="Nominal" class="form-control" name="Nominal"
                                                required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 pr-1">
                                        <div class="form-group">
                                            <label>Dokumen/Foto</label>
                                            <input type="file" class="form-control" name="Files" id="Files">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 pr-1">
                                        <div class="form-group">
                                            <input hidden type="text" id="LastUpdate" class="form-control"
                                                name="LastUpdate" required value="<?php echo date('Y-m-d h:i:sa'); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 pr-1">
                                        <div class="form-group">
                                            <input hidden type="text" class="form-control" name="UpdateBy" id="UpdateBy"
                                                value="">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary mr-2">Submit</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </form>
    </div>