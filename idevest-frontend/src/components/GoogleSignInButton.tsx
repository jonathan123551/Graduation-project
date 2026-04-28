import { Button } from "@/components/ui/button";
import { toast } from "@/hooks/use-toast";

export default function GoogleSignInButton({ label }: { label: string }) {
  const handleClick = () => {
    toast({
      title: "Coming Soon",
      description: "Google login will be added soon.",
    });
  };

  return (
    <Button
      type="button"
      variant="outline"
      onClick={handleClick}
      className="w-full"
    >
      {label}
    </Button>
  );
}