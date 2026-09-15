@extends('layout.layout')
@section('title', "Cycle Count")
@section('content')

    <!-- Responsive Datatable -->
    <div class="card">
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="dataTables_wrapper dt-bootstrap5 no-footer">
                <div class="card-header flex-column flex-md-row py-1">
                    <div class="card-header-elements ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary">Actions</button>
                            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"
                                data-bs-toggle="dropdown"></button>
                            <div class="dropdown-menu">                                
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('downloadTemplateLoadLocation') }}" target="_blank">
                                    <span id="downloadTemplateExcel">
                                        <i class='bx bxs-download'></i>
                                        <span class="ml-2 d-none d-sm-inline-block">Download Template</span>
                                    </span>
                                </a>
                                <a class="dropdown-item" href="{{ route('uploadcycledetail') }}">
                                    <span id="downloadTemplateExcel">
                                        <i class='bx bx-upload'></i>
                                        <span class="ml-2 d-none d-sm-inline-block">Upload Detail</span>
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>

                   
                    
                </div>
                <div class="card-datatable table-responsive">
                    <form class="form-horizontal" role="form" method="post" action="/cyclecountupd">
                    @csrf
                     <div class="form-group">
                        <div class="col-md-6">
                           <label>Site</label>
                           <input type="text" name="site" class="form-control" required="">
                        </div>
                        <div class="col-md-6">
                           <label>Item Number</label>
                           <input type="text" name="item" class="form-control" required="">
                        </div>
                        <div class="col-md-6">
                           <label>Lot Number</label>
                           <input type="text" name="lot" class="form-control" required="">
                        </div>
                        <div class="col-md-6">
                           <label>Warehouse</label>
                           <input type="text" name="wrh" class="form-control" required="">
                        </div>
                        <div class="col-md-6">
                           <label>Level</label>
                           <input type="text" name="level" class="form-control" required="">
                        </div>
                        <div class="col-md-6">
                           <label>BIN</label>
                           <input type="text" name="bin" class="form-control" required="">
                        </div>
                        <div class="col-md-6">
                           <label>Qty Oh</label>
                           <input type="text" name="qty" class="form-control" required="">
                        </div>
				    </div>		
                    <br></br>			    	
                     

					 
					 
					    	<div class="form-group">
                     <div class="col-md-6">
					    		<button type="submit" class="btn btn-danger btn-sm">Add</button>
                        </div>
					    	</div>                    
                    
					    </form> 
                </div>
            </div>
        </div>
    </div>
    <!--/ Responsive Datatable -->
@endsection

