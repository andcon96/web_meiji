define input parameter inpdomain as character no-undo.
define input parameter inpship as character no-undo.
define input parameter inpidref as character no-undo.

define output parameter outOK as logical no-undo initial false.
define output parameter outMsg as character no-undo initial "".

define temp-table temp
field t_shipper_nbr like abs_par_id
.

define output parameter table for temp.

for each abs_mstr where abs_domain = inpdomain and abs_shipfrom = inpship and
substring(abs__qad01, 81, 20) = inpidref no-lock:
    outOK = yes.
    create temp.
    assign
        t_shipper_nbr = abs_id
    .
end.

catch eSysError as Progress.Lang.SysError:
    outMsg = eSysError:GetMessage(1).
    delete object eSysError.
end catch.
