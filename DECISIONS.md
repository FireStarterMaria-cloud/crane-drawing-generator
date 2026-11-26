Decisions

1. Who is the User and what actually do they need?

   Sales Engineer or Design Engineer who communicates with the client first and shares a proposal.
   When a customer shares his/her basic requirements regarding capacity, span, height or so on, Engineer needs an instant preliminary 2D drawing or basic technical data table so that he can show the layout to the customer.
   and for this,engineer should be facilitated to save designs in database,reopen old designs, and to edit according to the changing desired requirements of customer.
   It is just a preliminary drawing to offer not a final factory manufacturing blueprint.


2. What did you assume?

   Actually I picked single-girder EOT (Overhead) because the reference drawing on my take-home assignment pg-04 was a single-girder crane. I used millimeters (mm) for dimensions and tonnes (t) for capacity because they are standard units in engineering drawings. 
   There was only one size in reference sheet so I assumed ranges like ..capacity 1t-20t , span 5000-30000 mm , and Lift height 2000-15000 mm ( this is my guess, I did not find a standard for it)
   Trolley or how span of crane varies for different weights were not mentioned in reference drawing ... so for them I assumed (10t/25m) simple proportional scaling and used (Estimate) label at every place.


3. Where did each formula or rule come from, and how sure are you?
   
   From the reference drawing, End clearance Min. 235 mm, Rail size 50x30 mm,  and default case numbers (Span 25000, overall length 25270, Wheelbase 3800, End carriage 4310, Runway height 8321, Lifting height 7900) girder depth Span/20 is my estimate, it is not from drawing.
   
    I am sure about (from drawing); overall length = Span + 270 mm (because 25270 - 25000 = 270), End carriage = Wheelbase + 510 mm (4310 - 3800 = 510), and Wheelbase ≈ Span / 6.58 (25000 / 3800) .. trolley weight (0.68t) and crane weight (8.9t) were scaled linearly according to capacity  and span.

   Actually, in the reference drawing  along with the 10t load,0.68t trolley, and 8.9t crane there was  mentioned Max static wheel load = 74.8 kN but when I used the basic formula my answer was ~72.6 kN (my formula doesn't involve dynamic factor) which doesn't match the exact 74.8 kN (about 3% lower) because I don't know how they measured impact factor or wheel distribution sooo I just flag it directly with (Estimate)


4. What did you leave out on purpose?

   It was a single-user local engineering tool so I left out User Login/Auth I thought time should be consumed on backend validation or accuracy of drawing. so I just focused on single-girder crane. Motors, gearboxes, pendant control, power feeding line and section A-A ... I left these out too from reference drawing so that 2D schematic and dimensions could be seen clearly.
   and  the truth is that the range of every input is under checking and there are 3 cross-field rules too. For instance, runway height must be at least 400 mm greater than lifting height, wheelbase must be 10-25% of span and  weight of trolley must be smaller than weight of crane( these limits are my estimates ) because I didn't have data of crane engineering standards soo I have not added real deflection or safety factor calculations.
   I used simple geometric symbols to show parametric SVG at every size , main dimension of front and plan view is at same scale and the sections like trolley,hook and beam are symbols which is not according to scale.


5. What three questions would you ask our engineers?

     1. What do the dimensions 620 mm and 8710 mm  in the reference drawing represent, and how do they change with capacity? and I think they are Hook approaches but I am not sure  Hook approaches Min. 920 mm and Min. 1150 mm ?

     so that I can calculate exact vertical clearances and girder profile in front elevation.

     2. How is the Max Static Wheel Load (74.8 kN on the 10t drawing) calculated from crane weight, trolley weight, and payload?

     my answer was not exactly 74.8 kN .. so I can make the calculations accurate in structural load after knowing this.

     3. Does end-carriage wheelbase (3800 mm) scale linearly with span, or do you use fixed standard sizes from a catalog?

      I scaled wheelbase with span in ratio but after I get the standard catalog sizes .. I will add dropdown/lookup rules.


6. What would you do with another week?

   Engineers need a .dxf file for editing in AutoCAD and .pdf title-block sheet  to send it to  the client ...so first of all I will add DXF and PDF export with SVG. Then, in the  place of assumptions for crane weight, trolley weight and wheelbase I will add real hoist/girder catalog rules and exact wheel-load formula.
   I will add double-girder templates for heavier capacities (>20t) and version history (v1,v2) for saved designs so that engineers can compare old revisions.

