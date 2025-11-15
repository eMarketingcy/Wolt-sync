import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { CheckCircle2, XCircle, Clock } from "lucide-react";

interface SyncRecord {
  id: string;
  timestamp: string;
  type: "manual" | "cron";
  status: "success" | "error" | "running";
  itemsSynced: number;
  duration: string;
}

const mockHistory: SyncRecord[] = [
  {
    id: "1",
    timestamp: "2025-11-14 10:30:45",
    type: "manual",
    status: "success",
    itemsSynced: 142,
    duration: "2m 15s",
  },
  {
    id: "2",
    timestamp: "2025-11-14 08:00:00",
    type: "cron",
    status: "success",
    itemsSynced: 138,
    duration: "1m 52s",
  },
  {
    id: "3",
    timestamp: "2025-11-14 06:00:00",
    type: "cron",
    status: "success",
    itemsSynced: 140,
    duration: "2m 8s",
  },
  {
    id: "4",
    timestamp: "2025-11-13 22:15:30",
    type: "manual",
    status: "error",
    itemsSynced: 0,
    duration: "0m 5s",
  },
  {
    id: "5",
    timestamp: "2025-11-13 20:00:00",
    type: "cron",
    status: "success",
    itemsSynced: 135,
    duration: "2m 3s",
  },
];

export const SyncHistoryTable = () => {
  const getStatusBadge = (status: SyncRecord["status"]) => {
    switch (status) {
      case "success":
        return (
          <Badge variant="outline" className="bg-success/10 text-success border-success/20">
            <CheckCircle2 className="h-3 w-3 mr-1" />
            Success
          </Badge>
        );
      case "error":
        return (
          <Badge variant="outline" className="bg-destructive/10 text-destructive border-destructive/20">
            <XCircle className="h-3 w-3 mr-1" />
            Error
          </Badge>
        );
      case "running":
        return (
          <Badge variant="outline" className="bg-primary/10 text-primary border-primary/20">
            <Clock className="h-3 w-3 mr-1" />
            Running
          </Badge>
        );
    }
  };

  const getTypeBadge = (type: SyncRecord["type"]) => {
    return type === "manual" ? (
      <Badge variant="secondary">Manual</Badge>
    ) : (
      <Badge variant="outline">Cron</Badge>
    );
  };

  return (
    <div className="rounded-md border border-border bg-card">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Timestamp</TableHead>
            <TableHead>Type</TableHead>
            <TableHead>Status</TableHead>
            <TableHead className="text-right">Items Synced</TableHead>
            <TableHead className="text-right">Duration</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {mockHistory.map((record) => (
            <TableRow key={record.id}>
              <TableCell className="font-medium">{record.timestamp}</TableCell>
              <TableCell>{getTypeBadge(record.type)}</TableCell>
              <TableCell>{getStatusBadge(record.status)}</TableCell>
              <TableCell className="text-right">{record.itemsSynced}</TableCell>
              <TableCell className="text-right text-muted-foreground">
                {record.duration}
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
};
