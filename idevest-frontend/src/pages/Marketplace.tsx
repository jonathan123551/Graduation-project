import { useState, useMemo } from "react";
import { useLanguage } from "@/i18n/LanguageContext";
import { useIdeas } from "@/hooks/useIdeas";

import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Slider } from "@/components/ui/slider";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { Checkbox } from "@/components/ui/checkbox";

import IdeaCard from "@/components/IdeaCard";

import { motion } from "framer-motion";
import {
  Search,
  Loader2,
  Lightbulb,
  CheckCircle,
  SlidersHorizontal,
  X,
} from "lucide-react";

interface IdeaRow {
  id: string;
  title: string;
  description: string;
  sector: string;
  location: string;
  capital_required: string;
  capital_required_usd: number | null;
  ai_score: number;
  risk_score: number;
  market_score: number;
  founder_id: string;
  created_at: string;
  profiles?: {
    full_name: string;
  } | null;
}

function parseCapitalToUSD(value: string | null | undefined): number {
  if (!value) return 0;

  const clean = value.toLowerCase().replace(/[,\s]/g, "");
  const num = parseFloat(clean.replace(/[^\d.]/g, ""));

  if (isNaN(num)) return 0;

  if (
    clean.includes("m") ||
    clean.includes("million") ||
    clean.includes("مليون")
  ) {
    return num * 1_000_000;
  }

  if (
    clean.includes("k") ||
    clean.includes("thousand") ||
    clean.includes("الف") ||
    clean.includes("ألف")
  ) {
    return num * 1_000;
  }

  if (clean.includes("جنيه") || clean.includes("egp")) {
    return num / 50;
  }

  return num;
}

export default function Marketplace() {
  const { t } = useLanguage();

  const { ideas, loading } = useIdeas();

  const [search, setSearch] = useState("");
  const [sectorFilters, setSectorFilters] = useState<string[]>([]);
  const [locationFilter, setLocationFilter] = useState("all");

  const [capitalRange, setCapitalRange] = useState<[number, number]>([
    0, 5000000,
  ]);

  const [riskRange, setRiskRange] = useState<[number, number]>([
    0, 100,
  ]);

  const [scoreMin, setScoreMin] = useState(0);
  const [sortBy, setSortBy] = useState("newest");

  const sectors = useMemo(() => {
    return Array.from(
      new Set(
        (ideas as IdeaRow[])
          .map((item) => item.sector)
          .filter(Boolean)
      )
    );
  }, [ideas]);

  const locations = useMemo(() => {
    return Array.from(
      new Set(
        (ideas as IdeaRow[])
          .map((item) => item.location)
          .filter(Boolean)
      )
    );
  }, [ideas]);

  const filteredIdeas = useMemo(() => {
    let result = [...(ideas as IdeaRow[])];

    if (search.trim()) {
      const q = search.toLowerCase();

      result = result.filter((item) => {
        return (
          item.title?.toLowerCase().includes(q) ||
          item.description?.toLowerCase().includes(q) ||
          item.sector?.toLowerCase().includes(q) ||
          item.location?.toLowerCase().includes(q)
        );
      });
    }

    if (sectorFilters.length > 0) {
      result = result.filter((item) =>
        sectorFilters.includes(item.sector)
      );
    }

    if (locationFilter !== "all") {
      result = result.filter(
        (item) => item.location === locationFilter
      );
    }

    result = result.filter((item) => {
      const capital =
        item.capital_required_usd ??
        parseCapitalToUSD(item.capital_required);

      return (
        capital >= capitalRange[0] &&
        capital <= capitalRange[1]
      );
    });

    result = result.filter((item) => {
      return (
        item.risk_score >= riskRange[0] &&
        item.risk_score <= riskRange[1]
      );
    });

    result = result.filter(
      (item) => item.ai_score >= scoreMin
    );

    if (sortBy === "highestScore") {
      result.sort((a, b) => b.ai_score - a.ai_score);
    } else if (sortBy === "lowestRisk") {
      result.sort((a, b) => a.risk_score - b.risk_score);
    } else if (sortBy === "lowestCapital") {
      result.sort((a, b) => {
        const aCap =
          a.capital_required_usd ??
          parseCapitalToUSD(a.capital_required);

        const bCap =
          b.capital_required_usd ??
          parseCapitalToUSD(b.capital_required);

        return aCap - bCap;
      });
    } else {
      result.sort(
        (a, b) =>
          new Date(b.created_at).getTime() -
          new Date(a.created_at).getTime()
      );
    }

    return result;
  }, [
    ideas,
    search,
    sectorFilters,
    locationFilter,
    capitalRange,
    riskRange,
    scoreMin,
    sortBy,
  ]);

  const activeFiltersCount =
    sectorFilters.length +
    (locationFilter !== "all" ? 1 : 0) +
    (capitalRange[0] > 0 ||
    capitalRange[1] < 5000000
      ? 1
      : 0) +
    (riskRange[0] > 0 ||
    riskRange[1] < 100
      ? 1
      : 0) +
    (scoreMin > 0 ? 1 : 0);

  const toggleSector = (sector: string) => {
    setSectorFilters((prev) =>
      prev.includes(sector)
        ? prev.filter((item) => item !== sector)
        : [...prev, sector]
    );
  };

  const clearFilters = () => {
    setSectorFilters([]);
    setLocationFilter("all");
    setCapitalRange([0, 5000000]);
    setRiskRange([0, 100]);
    setScoreMin(0);
    setSortBy("newest");
    setSearch("");
  };

  if (loading) {
    return (
      <div className="min-h-[60vh] flex items-center justify-center">
        <Loader2 className="h-7 w-7 animate-spin text-primary" />
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="mb-8">
        <div className="flex items-center gap-3 mb-3">
          <Lightbulb className="h-7 w-7 text-primary" />

          <h1 className="text-3xl font-bold">
            {t.marketplace?.title ||
              "Marketplace"}
          </h1>
        </div>

        <p className="text-muted-foreground">
          {t.marketplace?.subtitle ||
            "Explore investment-ready startup ideas"}
        </p>
      </div>

      <div className="glass rounded-2xl p-5 mb-6">
        <div className="grid md:grid-cols-3 gap-4">
          <div className="relative md:col-span-2">
            <Search className="absolute start-3 top-3 h-4 w-4 text-muted-foreground" />

            <Input
              value={search}
              onChange={(e) =>
                setSearch(e.target.value)
              }
              placeholder="Search ideas..."
              className="ps-10"
            />
          </div>

          <div className="flex gap-2">
            <Select
              value={sortBy}
              onValueChange={setSortBy}
            >
              <SelectTrigger>
                <SelectValue placeholder="Sort By" />
              </SelectTrigger>

              <SelectContent>
                <SelectItem value="newest">
                  Newest
                </SelectItem>

                <SelectItem value="highestScore">
                  Highest Score
                </SelectItem>

                <SelectItem value="lowestRisk">
                  Lowest Risk
                </SelectItem>

                <SelectItem value="lowestCapital">
                  Lowest Capital
                </SelectItem>
              </SelectContent>
            </Select>

            <Popover>
              <PopoverTrigger asChild>
                <Button variant="outline">
                  <SlidersHorizontal className="h-4 w-4 me-2" />
                  Filters

                  {activeFiltersCount > 0 && (
                    <Badge className="ms-2">
                      {activeFiltersCount}
                    </Badge>
                  )}
                </Button>
              </PopoverTrigger>

              <PopoverContent className="w-80 space-y-5">
                <div>
                  <p className="font-medium mb-2">
                    Sector
                  </p>

                  <div className="space-y-2">
                    {sectors.map((sector) => (
                      <label
                        key={sector}
                        className="flex items-center gap-2"
                      >
                        <Checkbox
                          checked={sectorFilters.includes(
                            sector
                          )}
                          onCheckedChange={() =>
                            toggleSector(sector)
                          }
                        />

                        <span>{sector}</span>
                      </label>
                    ))}
                  </div>
                </div>

                <div>
                  <p className="font-medium mb-2">
                    Location
                  </p>

                  <Select
                    value={locationFilter}
                    onValueChange={
                      setLocationFilter
                    }
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>

                    <SelectContent>
                      <SelectItem value="all">
                        All
                      </SelectItem>

                      {locations.map((loc) => (
                        <SelectItem
                          key={loc}
                          value={loc}
                        >
                          {loc}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <p className="font-medium mb-2">
                    Minimum AI Score
                  </p>

                  <Slider
                    value={[scoreMin]}
                    min={0}
                    max={100}
                    step={1}
                    onValueChange={(val) =>
                      setScoreMin(val[0])
                    }
                  />

                  <p className="text-sm text-muted-foreground mt-2">
                    {scoreMin}
                  </p>
                </div>

                <Button
                  variant="outline"
                  className="w-full"
                  onClick={clearFilters}
                >
                  <X className="h-4 w-4 me-2" />
                  Clear Filters
                </Button>
              </PopoverContent>
            </Popover>
          </div>
        </div>
      </div>

      {filteredIdeas.length === 0 ? (
        <div className="glass rounded-2xl p-10 text-center">
          <CheckCircle className="h-10 w-10 mx-auto mb-3 text-primary" />

          <h3 className="text-xl font-semibold mb-2">
            No ideas found
          </h3>

          <p className="text-muted-foreground">
            Try changing filters or search terms
          </p>
        </div>
      ) : (
        <motion.div
          layout
          className="grid md:grid-cols-2 xl:grid-cols-3 gap-6"
        >
          {filteredIdeas.map((idea, index) => (
            <IdeaCard
              key={idea.id}
              id={idea.id}
              title={idea.title}
              description={idea.description}
              sector={idea.sector}
              location={idea.location}
              founderName={
                idea.profiles?.full_name ||
                "Unknown"
              }
              aiScore={idea.ai_score || 0}
              riskScore={idea.risk_score || 0}
              marketScore={idea.market_score || 0}
              capitalRequired={
                idea.capital_required || "N/A"
              }
              index={index}
            />
          ))}
        </motion.div>
      )}
    </div>
  );
}