"use client";

import {
  AreaChart,
  Area,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from "recharts";

interface ClickChartProps {
  data: Array<{ date: string; clicks: number }>;
}

function CustomTooltip({
  active,
  payload,
  label,
}: {
  active?: boolean;
  payload?: Array<{ value: number }>;
  label?: string;
}) {
  if (!active || !payload?.length) return null;
  return (
    <div className="rounded-xl bg-white px-4 py-3 shadow-lg border border-border">
      <p className="text-xs font-medium text-muted-foreground mb-1">{label}</p>
      <p className="text-lg font-bold text-foreground">
        {payload[0].value.toLocaleString()}{" "}
        <span className="text-xs font-normal text-muted-foreground">
          clicks
        </span>
      </p>
    </div>
  );
}

export function ClickChart({ data }: ClickChartProps) {
  return (
    <div className="h-[300px] w-full">
      <ResponsiveContainer width="100%" height="100%">
        <AreaChart
          data={data}
          margin={{ top: 8, right: 8, left: -12, bottom: 0 }}
        >
          <defs>
            <linearGradient id="clickGradient" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stopColor="#2563eb" stopOpacity={0.3} />
              <stop offset="95%" stopColor="#2563eb" stopOpacity={0.02} />
            </linearGradient>
          </defs>
          <CartesianGrid
            strokeDasharray="3 3"
            stroke="#e2e8f0"
            vertical={false}
          />
          <XAxis
            dataKey="date"
            axisLine={false}
            tickLine={false}
            tick={{ fontSize: 12, fill: "#64748b" }}
            dy={8}
          />
          <YAxis
            axisLine={false}
            tickLine={false}
            tick={{ fontSize: 12, fill: "#64748b" }}
            allowDecimals={false}
            dx={-4}
          />
          <Tooltip
            content={<CustomTooltip />}
            cursor={{ stroke: "#2563eb", strokeWidth: 1, strokeDasharray: "4" }}
          />
          <Area
            type="monotone"
            dataKey="clicks"
            stroke="#2563eb"
            strokeWidth={2.5}
            fill="url(#clickGradient)"
            dot={{ r: 4, fill: "#2563eb", strokeWidth: 2, stroke: "#fff" }}
            activeDot={{
              r: 6,
              fill: "#2563eb",
              strokeWidth: 2,
              stroke: "#fff",
            }}
          />
        </AreaChart>
      </ResponsiveContainer>
    </div>
  );
}
