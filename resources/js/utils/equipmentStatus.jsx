import { Tag } from 'antd';

export function resolveStatusTags(equipment) {
    const statusName = equipment.unitstatus?.name?.toLowerCase() ?? '';

    if (statusName.includes('active')) {
        if (equipment.is_rfu) {
            return [<Tag color="success" key="rfu">RFU</Tag>];
        }

        return [<Tag color="error" key="bd">B/D</Tag>];
    }

    if (statusName.includes('scrap')) {
        return [<Tag key="scrap">Scrap</Tag>];
    }

    if (statusName.includes('sold')) {
        return [<Tag key="sold">Sold</Tag>];
    }

    if (statusName.includes('inactive') || statusName.includes('in-active')) {
        return [<Tag key="inactive">In-active</Tag>];
    }

    return equipment.unitstatus?.name ? [<Tag key="status">{equipment.unitstatus.name}</Tag>] : [];
}
