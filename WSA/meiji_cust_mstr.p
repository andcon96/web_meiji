define input parameter inpdomain as character no-undo.

define output parameter outOK as logical no-undo initial false.
define output parameter outMsg as character no-undo initial "".

define temp-table temp
field t_cust_domain like cm_domain
field t_cust_code   like cm_addr
field t_cust_name   like cm_sort
.

define output parameter table for temp.

for each cm_mstr where cm_domain = inpdomain no-lock:
	outOK = yes.
	create temp.
	assign
		t_cust_domain = cm_domain
		t_cust_code   = cm_addr
		t_cust_name   = cm_sort
	.
end.

catch eSysError as Progress.Lang.SysError:
    outMsg = eSysError:GetMessage(1).
    delete object eSysError.
end catch.
