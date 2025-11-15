import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { SyncStatusCard } from "@/components/SyncStatusCard";
import { SyncHistoryTable } from "@/components/SyncHistoryTable";
import { toast } from "sonner";
import { RefreshCw, Settings, Calendar, Package } from "lucide-react";

const Index = () => {
  const [isSyncing, setIsSyncing] = useState(false);

  const handleManualSync = () => {
    setIsSyncing(true);
    toast.info("Starting synchronization...");
    
    // Simulate sync process
    setTimeout(() => {
      setIsSyncing(false);
      toast.success("Synchronization completed successfully!", {
        description: "142 products updated with latest prices and images",
      });
    }, 2000);
  };

  return (
    <div className="min-h-screen bg-background">
      {/* Header */}
      <header className="border-b border-border bg-card">
        <div className="container mx-auto px-4 py-4">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold text-foreground">WooCommerce Sync Manager</h1>
              <p className="text-sm text-muted-foreground">Synchronize prices and images automatically</p>
            </div>
            <Button variant="outline" size="icon">
              <Settings className="h-4 w-4" />
            </Button>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="container mx-auto px-4 py-8">
        <div className="space-y-6">
          {/* Status Cards */}
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <SyncStatusCard
              title="Last Sync"
              value="10:30 AM"
              status="success"
              description="2 minutes ago"
            />
            <SyncStatusCard
              title="Products Synced"
              value="142"
              description="All products up to date"
            />
            <SyncStatusCard
              title="Next Scheduled"
              value="12:00 PM"
              status="pending"
              description="In 1 hour 30 minutes"
            />
            <SyncStatusCard
              title="Success Rate"
              value="98.5%"
              status="success"
              description="Last 30 days"
            />
          </div>

          {/* Manual Sync Card */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <RefreshCw className="h-5 w-5 text-primary" />
                Manual Synchronization
              </CardTitle>
              <CardDescription>
                Trigger an immediate sync of all WooCommerce products with the latest prices and images
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                <div className="flex-1">
                  <p className="text-sm text-muted-foreground mb-2">
                    This will sync all product prices and images from your source to WooCommerce.
                    The process typically takes 1-3 minutes depending on the number of products.
                  </p>
                </div>
                <Button
                  onClick={handleManualSync}
                  disabled={isSyncing}
                  className="w-full sm:w-auto"
                >
                  {isSyncing ? (
                    <>
                      <RefreshCw className="mr-2 h-4 w-4 animate-spin" />
                      Syncing...
                    </>
                  ) : (
                    <>
                      <RefreshCw className="mr-2 h-4 w-4" />
                      Sync Now
                    </>
                  )}
                </Button>
              </div>
            </CardContent>
          </Card>

          {/* Cron Settings Card */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Calendar className="h-5 w-5 text-primary" />
                Cron Schedule
              </CardTitle>
              <CardDescription>
                Automated synchronization schedule settings
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div className="flex items-center justify-between p-4 rounded-lg bg-muted">
                  <div className="flex items-center gap-3">
                    <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                      <Calendar className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                      <p className="font-medium text-foreground">Every 2 hours</p>
                      <p className="text-sm text-muted-foreground">Next run: 12:00 PM</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="h-2 w-2 rounded-full bg-success animate-pulse" />
                    <span className="text-sm font-medium text-success">Active</span>
                  </div>
                </div>
                
                <div className="grid gap-2 text-sm">
                  <div className="flex justify-between py-2 border-b border-border">
                    <span className="text-muted-foreground">Schedule Pattern</span>
                    <span className="font-medium text-foreground">0 */2 * * *</span>
                  </div>
                  <div className="flex justify-between py-2 border-b border-border">
                    <span className="text-muted-foreground">Total Runs Today</span>
                    <span className="font-medium text-foreground">5</span>
                  </div>
                  <div className="flex justify-between py-2">
                    <span className="text-muted-foreground">Success Rate (24h)</span>
                    <span className="font-medium text-success">100%</span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Sync History */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Package className="h-5 w-5 text-primary" />
                Synchronization History
              </CardTitle>
              <CardDescription>
                Recent sync operations and their results
              </CardDescription>
            </CardHeader>
            <CardContent>
              <SyncHistoryTable />
            </CardContent>
          </Card>
        </div>
      </main>
    </div>
  );
};

export default Index;
