import { Component, Inject } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';

@Component({
  selector: 'app-dialog-workflow',
  templateUrl: './dialog-workflow.component.html',
  styleUrls: ['./dialog-workflow.component.css']
})
export class DialogWorkflowComponent {
  constructor(
    public dialogRef: MatDialogRef<DialogWorkflowComponent>,
    @Inject(MAT_DIALOG_DATA) public data: any
  ) { }
}
